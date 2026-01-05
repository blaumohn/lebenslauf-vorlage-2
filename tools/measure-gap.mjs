import { chromium, firefox, webkit } from "@playwright/test";

const args = process.argv.slice(2);
const argValue = (name) => {
  const direct = args.find((arg) => arg.startsWith(`--${name}=`));
  if (direct) return direct.slice(name.length + 3);
  const index = args.findIndex((arg) => arg === `--${name}`);
  if (index !== -1 && args[index + 1]) return args[index + 1];
  return null;
};

if (args.includes("--help") || args.includes("-h")) {
  printUsage();
  process.exit(0);
}

const url = argValue("url");
if (!url) {
  printUsage();
  process.exit(1);
}

const browserName = (argValue("browser") || "chromium").toLowerCase();
const tagText = argValue("tag-text") || "Reaktive Komponenten";
const headingText = argValue("heading-text") || "Berufserfahrung";
const waitFor = argValue("wait-for") || "networkidle";

const launcher = getLauncher(browserName);
const browser = await launcher.launch({ headless: true });
const page = await browser.newPage();

try {
  await page.goto(url, { waitUntil: waitFor });
  const tag = page.getByText(tagText, { exact: true }).first();
  const heading = page
    .getByRole("heading", { name: headingText })
    .first();

  const tagBox = await tag.boundingBox();
  const headingBox = await heading.boundingBox();

  if (!tagBox) {
    throw new Error(`Tag not found: ${tagText}`);
  }
  if (!headingBox) {
    throw new Error(`Heading not found: ${headingText}`);
  }

  const tagRight = tagBox.x + tagBox.width;
  const headingLeft = headingBox.x;
  const gap = headingLeft - tagRight;

  console.log(
    JSON.stringify(
      {
        url,
        tagText,
        headingText,
        tagRight,
        headingLeft,
        gap,
      },
      null,
      2,
    ),
  );
} finally {
  await page.close();
  await browser.close();
}

function getLauncher(name) {
  switch (name) {
    case "firefox":
      return firefox;
    case "webkit":
      return webkit;
    case "chromium":
    default:
      return chromium;
  }
}

function printUsage() {
  const lines = [
    "Usage:",
    "  node tools/measure-gap.mjs --url <url> [--browser chromium|firefox|webkit]",
    "Options:",
    "  --tag-text <text>        default: Reaktive Komponenten",
    "  --heading-text <text>    default: Berufserfahrung",
    "  --wait-for <state>       default: networkidle",
  ];
  console.log(lines.join("\n"));
}
