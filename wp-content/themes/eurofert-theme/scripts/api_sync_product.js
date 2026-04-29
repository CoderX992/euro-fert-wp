const fs = require("fs"); //Node js Module called file system = fs ,
// Purpose: To read, write, and manipulate files and directories.
const path = require("path");
const csv = require("csv-parser");
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

const BASE_IMAGE_DIR =
  "C:\\Users\\Ahmad Safieddine\\OneDrive\\Desktop\\Place For My Stuff\\Eurofert files\\Content of Website\\Products\\Product Images\\";
const BASE_JSON_DIR = path.join(__dirname, "../datasheets/");
// --- THE CORE ENGINE ---

async function runProductSync() {
  const products = [];

  // Step 1: Read the CSV file you exported from Excel
  /*Initialize a ReadStream to handle I/O (Input/Output) efficiently*/
  fs.createReadStream(path.join(__dirname, "products.csv"))
    .pipe(
      csv({
        mapHeaders: ({ header }) => header.trim(),
        mapValues: ({ value }) => (typeof value === "string" ? value.trim() : value)
      })
    )
    .on("data", (row) => products.push(row))
    .on("end", async () => {
      /* 1- We turn the array of products into a 'Map'
         2- every 'Key' (the slug) must be unique
         */
      const validProducts = products.filter((item) => item.slug && item.slug.trim() != "");
      const uniqueProducts = Array.from(new Map(validProducts.map((item) => [item.slug, item])).values());

      console.log(
        `🚀 Found ${products.length} rows in CSV. Filtered down to ${uniqueProducts.length} unique products.`
      );

      for (const item of uniqueProducts) {
        try {
          console.log(`\n--- Processing: ${item.title} ---`);
          //"Does this slug already exist?"
          let existingID = await getProductIdBySlug(item.slug);
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
          if (item.local_image_path && item.local_image_path.trim() !== "") {
            //check for empty column in the excel sheet
            // Section A : find Image path = the Base Folder + Category Folder + Filename
            const imgPath = path.join(BASE_IMAGE_DIR, item.category_slug, item.local_image_path.trim());

            if (fs.existsSync(imgPath)) {
              console.log(` found and Uploading: ${item.local_image_path}...`);
              featuredMediaId = await uploadMedia(imgPath);
            } else {
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
            title: item.title,
            slug: item.slug,
            content: item.content,
            status: "publish",
            featured_media: featuredMediaId,
            // Link to the custom taxonomy
            fertilizer_category: categoryId ? [categoryId] : [],
            // Map the ACF fields
            acf: {
              subtitle: item.subtitle,
              key_benefits: item.key_benefits,
              formula: item.formula,
              nutrient_table_rows: item.nutrient_rows
                ? item.nutrient_rows
                    .split("\\n")
                    .map((line) => line.trim())
                    .join("\n")
                : ""
            },
            // Map the CMB2 table field
            meta: {
              reco_rows: recommendationsData
            }
          };

          const response = await fetch(targetUrl, {
            method: requestMethod, // Dynamically switches between "POST" and "PUT"
            headers: {
              "Content-Type": "application/json",
              Authorization: authHeader
            },
            body: JSON.stringify(productData) // The package prepared in Section D
          });

          if (response.ok) {
            console.log(`✅ Success! ${item.title} is live.`);
          } else {
            const error = await response.json();
            console.error(`❌ Server rejected ${item.title}:`, error.message);
          }
        } catch (err) {
          console.error(`❌ Error processing ${item.title}:`, err.message);
        }
      }
      console.log("\n🏁 All products processed!");
    });
} //end of Async function

// --- HELPER FUNCTIONS ---

// Function to upload images to the Media Library
async function uploadMedia(filePath) {
  const fileName = path.basename(filePath);
  const fileData = fs.readFileSync(filePath);

  const response = await fetch(`${LIVE_SERVER_URL}/media`, {
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

// Function to turn a slug like 'colfert-essential' into an ID like 3
async function getCategoryIdBySlug(slug) {
  const response = await fetch(`${LIVE_SERVER_URL}/fertilizer_category?slug=${slug}`, {
    headers: { Authorization: authHeader }
  });
  const data = await response.json();
  return data.length > 0 ? data[0].id : null;
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

runProductSync();
