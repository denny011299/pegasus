const fs = require("fs");
const p = "public/Custom_js/Backoffice/Production/Production_Planning.js";
let t = fs.readFileSync(p, "utf8");
const start = t.indexOf("/** Paksa input + picker Job = state ter-apply");
const end = t.indexOf("function ppJobTabIsActive()");
if (start < 0 || end < 0) {
  console.log("markers", start, end);
  process.exit(1);
}
const neu = `function ppPaintJobDateInput() {
    ppPaintDateInput($("#pp_job_filter_date"), ppJobFilterDateStart, ppJobFilterDateEnd);
}

function initPpJobDateFilter() {
    ppBindSharedDatePicker($("#pp_job_filter_date"));
}

`;
fs.writeFileSync(p, t.slice(0, start) + neu + t.slice(end));
console.log("OK");
