const fs = require('node:fs');
const {execFileSync} = require('node:child_process');
for (const file of execFileSync('git', ['ls-files', '-z', '--', '*.php']).toString().split('\0').filter(Boolean)) {
  const source = fs.readFileSync(file, 'utf8');
  for (const tag of source.match(/<(?:script|link)\b[^>]*\b(?:src|href)="https:\/\/(?:cdnjs.cloudflare.com|cdn.jsdelivr.net)\/[^"\s]+"[^>]*>/g) || []) {
    if (!/integrity="sha(?:256|384|512)-[A-Za-z0-9+/=]+"/.test(tag) || !/crossorigin="anonymous"/.test(tag)) {
      throw new Error('Unpinned CDN resource in ' + file + ': ' + tag);
    }
  }
}
console.log('CDN integrity guard tests passed.');
