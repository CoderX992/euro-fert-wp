const fs = require("fs"); //Node js Module called file system = fs ,
// Purpose: To read, write, and manipulate files and directories.
const path = require("path");
const xlsx = require("xlsx");
require("dotenv").config({ path: path.join(__dirname, "../.env") }); // Load environment variables from .env file

// --- CONFIGURATION ---
const LIVE_SERVER_URL = process.env.LIVE_SERVER_URL;
const USERNAME = process.env.WP_USERNAME;
const APP_PASSWORD = process.env.WP_APP_PASSWORD; //password for the API

if (!LIVE_SERVER_URL || !USERNAME || !APP_PASSWORD) {
  throw new Error("Missing required env vars. Set LIVE_SERVER_URL, WP_USERNAME, WP_APP_PASSWORD in .env");
}

// authentication header for the API, part of the http request
const authHeader = "Basic " + Buffer.from(`${USERNAME}:${APP_PASSWORD}`).toString("base64");

const BASE_IMAGE_DIR = process.env.LOCAL_IMAGE_DIR;
const BASE_JSON_DIR = path.join(__dirname, "../datasheets/");
const PROGRESS_FILE = path.join(__dirname, ".sync_progress.json");

if (!BASE_IMAGE_DIR) {
  throw new Error("Missing required env var: LOCAL_IMAGE_DIR must be set in your .env file.");
}

// Reads the --dry-run flag from the terminal command. process.argv is an array
/* e.g. "node api_sync_product.js --dry-run" => process.argv = ["node", "api_sync_product.js", "--dry-run"]*/
const DRY_RUN = process.argv.includes('--dry-run') || process.argv.includes('dry-run');

// --- THE CORE ENGINE ---

async function runProductSync() {
  const products = [];
  // Create an array to hold the slugs we've already synced
  let syncedSlugs = [];
  // If the progress file exists, open it and read the list of slugs
  if (fs.existsSync(PROGRESS_FILE)) {
    const rawData = fs.readFileSync(PROGRESS_FILE, "utf8");
    syncedSlugs = JSON.parse(rawData);
    console.log(`\n📂 Found progress file. Skipping ${syncedSlugs.length} already synced products.`);
  }

  // Read the Excel file directly
  const excelPath = path.join(__dirname, "products.xlsx");
  if (!fs.existsSync(excelPath)) {
    console.error(`❌ Cannot find ${excelPath}`);
    return;
  }

  const workbook = xlsx.readFile(excelPath);
  const sheetName = workbook.SheetNames[0]; // grab the first sheet
  const rawProducts = xlsx.utils.sheet_to_json(workbook.Sheets[sheetName], { defval: "" });

  // Clean the headers and values in the excell
  for (const row of rawProducts) {
    const cleanRow = {};
    for (const [key, value] of Object.entries(row)) {
      cleanRow[key.trim()] = typeof value === "string" ? value.trim() : value;
    }
    products.push(cleanRow);
  }

  /* 1- We turn the array of products into a 'Map'
     2- every 'Key' (the slug) must be unique
     */
  const validProducts = products.filter((item) => item.slug && item.slug.trim() != "");
  const uniqueProducts = Array.from(new Map(validProducts.map((item) => [item.slug, item])).values());

  console.log(`🚀 Found ${products.length} rows in Excel. Filtered down to ${uniqueProducts.length} unique products.`);

  for (const item of uniqueProducts) {
    try {
      // Check if we should skip this product
      if (syncedSlugs.includes(item.slug)) {
        // Already synced in a previous run, skip it entirely
        continue;
      }

      console.log(`\n--- Processing: ${item.title} ---`);
      //"Does this slug already exist?"
      let existingID = await getProductIdBySlug(item.slug);
      // Guard: if this is a brand new product but has no title, skip it.
      if (!existingID && (!item.title || item.title.trim() === "")) {
        console.log(`⛔ SKIPPED: "${item.slug}" has no title and doesn't exist yet.`);
        continue; // jumps to the next item in the for loop, skipping this product
      }

      let requestMethod = "POST";

      let targetUrl = `${LIVE_SERVER_URL}/eurofert_product`;
      // 1.  Check if this product slug is already in the database
      if (existingID) {
        requestMethod = "PUT";
        targetUrl = `${targetUrl}/${existingID}`;
        console.log(`🔄 Found existing product (ID: ${existingID}). Updating instead of creating.`);
      } else {
        console.log(`🆕 No existing product found. Creating as new.`);
      }

      // Handle Featured Image
      let featuredMediaId = null;

      //check for empty column in the excel sheet
      if (item.local_image_path && item.local_image_path.trim() !== "") {
        // Section A : find Image path = the Base Folder + Category Folder + Filename
        const imgPath = path.join(BASE_IMAGE_DIR, item.category_slug, item.local_image_path.trim());

        if (fs.existsSync(imgPath)) {
          const fileName = path.basename(imgPath); // extracts just "colfert.webp" from the full path
          // First check if this image already exists in the WP media library

          if (DRY_RUN) {
            // Dry-run: skip all server calls — just confirm the file exists locally
            // We set a readable placeholder so the printed payload shows the filename
            featuredMediaId = `image: ${fileName}`;
            console.log(`\n🖼️  IMAGE QUEUED: ${fileName}\n`);
          } else {
            // LIVE Run

            featuredMediaId = await getMediaIdByFilename(fileName);

            if (featuredMediaId) {
              // Image already uploaded before — reuse its ID, skip the upload
              console.log(`🖼️ Image "${fileName}" already in media library (ID: ${featuredMediaId}). Reusing.`);
            } else {
              // Not found — upload fresh and get the new ID
              console.log(`⬆️ Uploading: ${fileName}...`);

              try {
                featuredMediaId = await uploadMedia(imgPath);
              } catch (imgErr) {
                console.error(`⚠️ Image Upload Failed for ${fileName}:`, imgErr.message);
                console.log(`   Continuing to sync product text data without the image.`);
                featuredMediaId = null; // Ensure it stays null so the payload
              }
            }
          }
        } // end if  fs.existsSync(imgPath)
        else {
          console.log(`⚠️ WARNING: Image not found at ${imgPath}. Skipping image.`);
        }
      }

      // Section B : Find Category ID using the slug (e.g., 'colfert-essential')
      const categoryId = await getCategoryIdBySlug(item.category_slug);

      // Section C :  Read  JSON from the external file to get the recommendation table
      let recommendationsData = [];
      if (item.recommendations_file_path && item.recommendations_file_path !== "") {
        // Glues the Base JSON folder + the Filename
        const jsonPath = path.join(BASE_JSON_DIR, item.recommendations_file_path.trim());
        if (fs.existsSync(jsonPath)) {
          const jsonRaw = fs.readFileSync(jsonPath, "utf8");
          recommendationsData = JSON.parse(jsonRaw);
          console.log(`📄 Reading JSON from: ${jsonPath}`);
        } else {
          console.log(`⚠️ WARNING: JSON file not found at: ${jsonPath}. Skipping table.`);
        }
      }

      // Section D Create the Product
      const productData = {
        slug: item.slug,
        status: "publish"
      };

      if (item.title) productData.title = item.title;
      if (item.content) productData.content = item.content;
      if (featuredMediaId) productData.featured_media = featuredMediaId;
      if (categoryId) productData.fertilizer_category = [categoryId];

      // Build the ACF sub-object only for columns that have data
      const acf = {};
      if (item.subtitle) {
        acf.subtitle = item.subtitle.toString().split("\\n").join("\n");
      }
      if (item.key_benefits) {
        acf.key_benefits = item.key_benefits.toString().split("\\n").join("\n");
      }
      if (item.formula) acf.formula = item.formula;

      if (item.nutrient_rows) {
        acf.nutrient_table_rows = item.nutrient_rows
          .split(/\\n|\r?\n/) // split the string into an array at each \n character
          .map((line) => line.trim()) // trim() removes leading/trailing whitespace from each line
          .join("\n"); // rejoin the array back into a single string
      }

      // Only attach acf to the payload if at least one ACF field had data
      if (Object.keys(acf).length > 0) productData.acf = acf;

      // Only send recommendations if the JSON file was found and had content
      if (recommendationsData.length > 0) productData.meta = { reco_rows: recommendationsData };

      if (DRY_RUN) {
        // Dry-run: skip the actual server request, just show what would be sent
        console.log(`🧪 DRY RUN: Would ${requestMethod} to ${targetUrl}`);

        if (productData.meta?.reco_rows?.length > 0) {
          console.log(`\n📊 RECOMMENDATIONS TABLE (${productData.meta.reco_rows.length} rows):`);
          console.log(JSON.stringify(productData.meta.reco_rows, null, 2));
        }
        // JSON.stringify(obj, null, 2) pretty-prints the object with 2-space indentation
        console.log(`   Full payload:\n${JSON.stringify(productData, null, 2)}`);
      } else {
        const response = await fetchWithRetry(targetUrl, {
          method: requestMethod, // Dynamically switches between "POST" and "PUT"
          headers: {
            "Content-Type": "application/json",
            Authorization: authHeader
          },
          body: JSON.stringify(productData) // The package prepared in Section D
        });

        if (response.ok) {
          console.log(`✅ Success! ${item.title} is live.`);
          //Save progress! Add slug to array and rewrite the JSON file

          syncedSlugs.push(item.slug); // records the slug
          // JSON.stringify(array, null, 2) makes the file readable
          fs.writeFileSync(PROGRESS_FILE, JSON.stringify(syncedSlugs, null, 2));
        } else {
          const error = await response.json();
          console.error(`❌ Server rejected ${item.title}:`, error.message);
        }
      } //end if DRY_RUN
      //closing try
    } catch (err) {
      console.error(`❌ Error processing ${item.title}:`, err.message);
    }
  } //end of for loop

  console.log("\n🏁 All products processed!");
} //end of Async function

// --- HELPER FUNCTIONS ---

// Function to upload images to the Media Library
async function uploadMedia(filePath) {
  const fileName = path.basename(filePath);
  const fileData = fs.readFileSync(filePath);
  // Using fetchWithRetry to handle server overload and sending response
  const response = await fetchWithRetry(`${LIVE_SERVER_URL}/media`, {
    method: "POST",
    headers: {
      "Content-Type": fileName.endsWith(".webp") ? "image/webp" : "image/png",
      "Content-Disposition": `attachment; filename="${fileName}"`,
      Authorization: authHeader
    },
    body: fileData
  });

  const data = await response.json();
  return data.id; // Returns the numeric ID WordPress assigned
}

async function getMediaIdByFilename(filename) {
  // path.parse("colfert.webp").name => "colfert" (strips the extension)
  // We search without extension because WP's search is text-based, not exact filename
  const searchName = path.parse(filename).name;
  const response = await fetch(
    // encodeURIComponent() makes the filename URL-safe (handles spaces, special chars)
    `${LIVE_SERVER_URL}/media?search=${encodeURIComponent(searchName)}&per_page=1`,
    { headers: { Authorization: authHeader } }
  );
  const data = await response.json(); // WordPress returns an array of matching media items
  // WP's search is fuzzy (e.g. "colfert" might match "colfert-old" too),
  // so we verify the result ends with our exact filename
  if (data.length > 0 && data[0].source_url.endsWith(filename)) {
    return data[0].id; // exact match found — return its ID
  }
  return null; // no match — caller will upload fresh
}

// Function to turn a slug like 'colfert-essential' into an ID like 3
async function getCategoryIdBySlug(slug) {
  const response = await fetch(`${LIVE_SERVER_URL}/fertilizer_category?slug=${slug}`, {
    headers: { Authorization: authHeader }
  });
  const data = await response.json();
  return data.length > 0 ? data[0].id : null;
}

// Wrapper for fetch() that automatically retries if the server is overloaded (500, 502, 503)
// or rate limits us (429). It waits 3 seconds before trying again.
async function fetchWithRetry(url, options, retries = 2) {
  for (let i = 0; i < retries; i++) {
    const response = await fetch(url, options);
    // If successful, or if it's a normal error (like 400 Bad Request), return immediately
    if (response.ok || ![429, 500, 502, 503, 504].includes(response.status)) {
      return response;
    }
    // If it's a server overload error, wait 3 seconds and loop again
    console.log(`⏳ Server returned ${response.status}. Retrying in 3 seconds... (${i + 1}/${retries})`);
    await new Promise((resolve) => setTimeout(resolve, 3000));
  } //end of for loop

  return await fetch(url, options);
}

// Task: Ask WordPress if a product with this specific slug already exists.
// Returns: The numeric ID of the product if found, or null if it's new.
async function getProductIdBySlug(slug) {
  const response = await fetch(`${LIVE_SERVER_URL}/eurofert_product?slug=${slug}`, {
    headers: { Authorization: authHeader }
  });

  // WordPress returns an Array [].
  // If the array is empty, the product doesn't exist.
  const data = await response.json();

  return data.length > 0 ? data[0].id : null;
}

// Wrapper for fetch() that automatically retries if the server is overloaded (500, 502, 503)

runProductSync();
