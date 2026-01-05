import fs from "node:fs";
import os from "node:os";
import path from "node:path";
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

const configPath = argValue("config");
const browserName = (argValue("browser") || "chromium").toLowerCase();
const userDataDir = argValue("user-data-dir");
const executableOverride = argValue("executable");
const fullPage = args.includes("--full-page");

const entries = configPath ? loadConfig(configPath) : [singleEntry()];
const launcher = getLauncher(browserName);
const sandboxHome = path.resolve("var/tmp/pw-home");
const sandboxCache = path.resolve("var/tmp/pw-cache");
fs.mkdirSync(sandboxHome, { recursive: true });
fs.mkdirSync(sandboxCache, { recursive: true });
const launchEnv = {
  ...process.env,
  HOME: sandboxHome,
  XDG_CACHE_HOME: sandboxCache,
};

const executablePath = resolveExecutablePath(
  browserName,
  launcher.executablePath(),
  executableOverride,
);

const browserArgs =
  browserName === "chromium"
    ? ["--disable-crash-reporter", "--disable-crashpad", "--disable-features=Crashpad"]
    : [];

const context = userDataDir
  ? await launcher.launchPersistentContext(userDataDir, {
      viewport: null,
      deviceScaleFactor: 1,
      env: launchEnv,
      args: browserArgs,
      ...(executablePath ? { executablePath } : {}),
    })
  : await launcher.launch({
      headless: true,
      env: launchEnv,
      args: browserArgs,
      ...(executablePath ? { executablePath } : {}),
    });

try {
  for (const entry of entries) {
    await takeScreenshot(context, entry, fullPage);
  }
} finally {
  await context.close();
}

function singleEntry() {
  const url = argValue("url");
  const out = argValue("out");
  if (!url || !out) {
    printUsage();
    process.exit(1);
  }
  return buildEntry({ url, out });
}

function loadConfig(filePath) {
  const resolved = path.resolve(filePath);
  const raw = fs.readFileSync(resolved, "utf8");
  const data = JSON.parse(raw);
  if (!Array.isArray(data)) {
    throw new Error("Screenshot config must be an array.");
  }
  return data.map((entry) => buildEntry(entry));
}

function buildEntry(entry) {
  if (!entry?.url || !entry?.out) {
    throw new Error("Each config entry needs url and out.");
  }
  return {
    url: entry.url,
    out: entry.out,
    width: entry.width ?? 1200,
    height: entry.height ?? 900,
    waitFor: entry.waitFor ?? "networkidle",
  };
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

async function takeScreenshot(context, { url, out, width, height, waitFor }, fullPage) {
  const outputPath = path.resolve(out);
  fs.mkdirSync(path.dirname(outputPath), { recursive: true });

  const page = await context.newPage({
    viewport: { width, height },
  });
  await page.goto(url, { waitUntil: waitFor });
  await page.waitForTimeout(400);
  await page.screenshot({ path: outputPath, fullPage });
  await page.close();
}

function resolveExecutablePath(browserName, defaultPath, override) {
  const envOverride =
    process.env.PLAYWRIGHT_EXECUTABLE_PATH ||
    (browserName === "chromium"
      ? process.env.PLAYWRIGHT_CHROMIUM_PATH
      : browserName === "firefox"
        ? process.env.PLAYWRIGHT_FIREFOX_PATH
        : process.env.PLAYWRIGHT_WEBKIT_PATH);

  const candidate = override || envOverride;
  if (candidate && fs.existsSync(candidate)) {
    return candidate;
  }

  if (defaultPath && fs.existsSync(defaultPath)) {
    return null;
  }

  if (browserName === "chromium") {
    const fallback = findChromiumFallback();
    if (fallback) {
      return fallback;
    }
  }

  return null;
}

function findChromiumFallback() {
  const cacheRoots = new Set();
  if (process.env.PLAYWRIGHT_BROWSERS_PATH) {
    cacheRoots.add(process.env.PLAYWRIGHT_BROWSERS_PATH);
  }

  const home = os.homedir();
  cacheRoots.add(path.join(home, "Library", "Caches", "ms-playwright"));
  cacheRoots.add(path.join(home, ".cache", "ms-playwright"));

  for (const root of cacheRoots) {
    if (!root || !fs.existsSync(root)) {
      continue;
    }
    const dirs = fs.readdirSync(root, { withFileTypes: true });
    for (const dir of dirs) {
      if (!dir.isDirectory() || !dir.name.startsWith("chromium-")) {
        continue;
      }
      const base = path.join(root, dir.name);
      const candidates = [
        path.join(
          base,
          "chrome-mac-arm64",
          "Google Chrome for Testing.app",
          "Contents",
          "MacOS",
          "Google Chrome for Testing",
        ),
        path.join(
          base,
          "chrome-mac-x64",
          "Google Chrome for Testing.app",
          "Contents",
          "MacOS",
          "Google Chrome for Testing",
        ),
      ];
      for (const candidate of candidates) {
        if (fs.existsSync(candidate)) {
          return candidate;
        }
      }
    }
  }

  return null;
}

function printUsage() {
  const lines = [
    "Usage:",
    "  node tools/screenshot.mjs --url <url> --out <file> [--width <px>] [--height <px>]",
    "  node tools/screenshot.mjs --config <file.json> [--browser chromium|firefox|webkit]",
    "  node tools/screenshot.mjs --config <file.json> --user-data-dir <dir>",
    "  node tools/screenshot.mjs --config <file.json> --executable <path>",
    "",
    "Config format:",
    '  [{"url":"http://127.0.0.1:8080","out":"var/tmp/php.png","width":1200,"height":900,"waitFor":"networkidle"}]',
  ];
  console.log(lines.join("\n"));
}
