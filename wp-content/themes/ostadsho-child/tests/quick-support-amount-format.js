const fs = require('fs');
const vm = require('vm');
const assert = require('assert');
const input = { value:'', selectionStart:0, handlers:{}, addEventListener(k, fn){ this.handlers[k]=fn; }, setSelectionRange(a){ this.selectionStart=a; } };
const field = { querySelector(){return input;}, appendChild(){} };
const form = {dataset:{}, getAttribute(){return JSON.stringify({category_id:1, suggested_amounts:[]});}, querySelector(){return field;} };
const document = {readyState:'complete', querySelectorAll(){return [form];}, addEventListener(){}, documentElement:null};
vm.runInNewContext(fs.readFileSync(__dirname + '/../assets/js/ba-quick-support-form.js','utf8'), {document,window:{},Intl,Number,Event:function(){}});
function inputValue(value) { input.value=value; input.selectionStart=value.length; input.handlers.input(); return input.value; }
assert.strictEqual(inputValue('1234567'), '1,234,567');
assert.strictEqual(inputValue('۱۲۳۴۵۶۷'), '1,234,567');
assert.strictEqual(inputValue('12345.67'), '12,345.67');
assert.strictEqual(inputValue('1,234,567'), '1,234,567');
console.log('PASS quick support amount formatting and decimal preservation');
