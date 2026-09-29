const fs = require("fs");
const readline = require("readline");
const p =
  "C:/Users/Ruben/.cursor/projects/d-Ruben-Data-Kerja-Git-OKEJOB-PEGASUS-PMI-pegasus/agent-transcripts/6c29f7d6-3d99-43ac-85ce-dcd51e15c5b7/6c29f7d6-3d99-43ac-85ce-dcd51e15c5b7.jsonl";
const rl = readline.createInterface({
  input: fs.createReadStream(p),
  crlfDelay: Infinity,
});
let n = 0;
rl.on("line", (line) => {
  n++;
  if (
    !line.includes("wo-materials.blade.php") ||
    !line.includes("Ambil Bahan") ||
    !line.includes('"Write"')
  ) {
    return;
  }
  const obj = JSON.parse(line);
  const dig = (o) => {
    if (!o || typeof o !== "object") return null;
    if (
      o.name === "Write" &&
      o.input &&
      String(o.input.path || "").includes("wo-materials")
    ) {
      return o.input.contents;
    }
    if (Array.isArray(o)) {
      for (const x of o) {
        const r = dig(x);
        if (r) return r;
      }
      return null;
    }
    for (const v of Object.values(o)) {
      const r = dig(v);
      if (r) return r;
    }
    return null;
  };
  const c = dig(obj.message);
  if (c) {
    fs.writeFileSync("tmp/wo-materials-old.blade.php", c);
    console.log("saved", c.length, "at", n);
    rl.close();
  }
});
