import fs from "node:fs";
import path from "node:path";
import { PNG } from "pngjs";
import pixelmatch from "pixelmatch";

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

const aPath = argValue("a");
const bPath = argValue("b");
const outPath = argValue("out");
const threshold = Number(argValue("threshold") ?? "0.1");

if (!aPath || !bPath) {
  printUsage();
  process.exit(1);
}

const imgA = PNG.sync.read(fs.readFileSync(aPath));
const imgB = PNG.sync.read(fs.readFileSync(bPath));

if (imgA.width !== imgB.width || imgA.height !== imgB.height) {
  throw new Error("Images must have the same dimensions.");
}

const { width, height } = imgA;
const diff = new PNG({ width, height });
const diffPixels = pixelmatch(
  imgA.data,
  imgB.data,
  diff.data,
  width,
  height,
  { threshold },
);

const totalPixels = width * height;
const diffPercent = (diffPixels / totalPixels) * 100;

const result = {
  a: path.resolve(aPath),
  b: path.resolve(bPath),
  width,
  height,
  diffPixels,
  diffPercent,
  threshold,
};

if (outPath) {
  fs.writeFileSync(outPath, PNG.sync.write(diff));
  result.out = path.resolve(outPath);
}

console.log(JSON.stringify(result, null, 2));

function printUsage() {
  const lines = [
    "Usage:",
    "  node tools/pixel-diff.mjs --a <image.png> --b <image.png> [--out <diff.png>] [--threshold 0.1]",
  ];
  console.log(lines.join("\n"));
}
