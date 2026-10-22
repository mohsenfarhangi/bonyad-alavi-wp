/* Isolated, executable JS regression for the public duplicate/reference recovery. */
'use strict';
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');

const root = path.join(__dirname, '..');
const source = fs.readFileSync(path.join(root, 'assets/js/frontend.js'), 'utf8');
const css = fs.readFileSync(path.join(root, 'assets/css/frontend.css'), 'utf8');
const renderer = fs.readFileSync(path.join(root, 'src/Form/Renderer.php'), 'utf8');

const start = source.indexOf('  function otpRecoveryMarkup(form, payload) {');
const end = source.indexOf('  function showDuplicateNotice(form, payload, anchor) {', start);
assert.ok(start >= 0 && end > start, 'Recovery implementation not found');

let responses = [];
let requests = [];
let navigated = [];
let fields = [['afe_form_slug','jihadi-group-registration'], ['afe_data[group_mobile]','09121234567']];
const input = {value:'PRIVATE-TRACKING', disabled:false, focused:false, focus(){this.focused=true;}};
const feedback = {textContent:'',hidden:true};
const area = {};
const button = {disabled:false,closest(selector){return selector==='[data-afe-duplicate-recovery]'?area:null;}};
const form = {dataset:{afeDuplicateTracking:'1'}};

function duplicateRequestData() {
  const values = new Map(fields);
  return {
    entries(){return [...values.entries()][Symbol.iterator]();},
    set(k,v){values.set(k,v);},
    get(k){return values.get(k);}
  };
}
function qs(areaOrForm, selector) {
  return ({
    '.afe-duplicate-recovery__input':input,
    '.afe-duplicate-recovery__feedback':feedback,
  })[selector] || null;
}
function escapeAttr(text) { return String(text).replace(/&/g,'&amp;').replace(/"/g,'&quot;'); }
async function fetch(url, options) {
  requests.push({url,options});
  const spec = responses.shift();
  if (spec instanceof Error) throw spec;
  return {ok:true,json:async()=>spec};
}

const context = {
  cfg:{ajaxUrl:'https://example.test/wp-admin/admin-ajax.php'},
  duplicateRequestData, qs, fetch, escapeAttr, URL, Array, JSON,
  window:{location:{href:'https://example.test/jihadi/',origin:'https://example.test',assign(url){navigated.push(url);}}},
};
vm.createContext(context);
vm.runInContext(source.slice(start,end)+'\nthis.render = duplicateResumeUi; this.verify = verifyDuplicateRecovery;',context);
const render = context.render, verify = context.verify;

(async()=>{
  const unauthenticated = render(form,{behavior:'reference',duplicate:true});
  assert.match(unauthenticated,/کد رهگیری ثبت قبلی/);
  assert.match(unauthenticated,/afe-duplicate-recovery__submit/);
  assert.doesNotMatch(unauthenticated,/href=/);
  assert.doesNotMatch(unauthenticated,/PRIVATE-TRACKING/);
  assert.equal(render({dataset:{afeDuplicateTracking:'0'}},{behavior:'reference',duplicate:true}).includes('afe-duplicate-recovery__input'),false);
  assert.equal(render(form,{behavior:'block',duplicate:true}), '');
  assert.match(render(form,{behavior:'block',resume_help:true}),/afe-duplicate-recovery__submit/);
  assert.match(render(form,{behavior:'reference',edit_url:'https://example.test/jihadi/?afe_tracking=ok'}),/href="https:\/\/example.test\/jihadi\//);

  input.value='';
  await verify(form,button);
  assert.match(feedback.textContent,/وارد کنید/);
  assert.equal(requests.length,0);
  assert.equal(input.focused,true);

  input.value='PRIVATE-TRACKING';
  responses.push({success:true,data:{duplicate:true,behavior:'reference',edit_url:'https://example.test/jihadi/?afe_tracking=PRIVATE-TRACKING'}});
  await verify(form,button);
  assert.deepEqual(navigated,['https://example.test/jihadi/?afe_tracking=PRIVATE-TRACKING']);
  assert.equal(requests[0].options.method,'POST');
  assert.equal(requests[0].options.credentials,'same-origin');
  assert.equal(requests[0].options.body.get('_afe_tracking'),'PRIVATE-TRACKING');
  assert.equal(requests[0].options.body.get('afe_form_slug'),'jihadi-group-registration');
  assert.equal(button.disabled,false);
  assert.equal(input.disabled,false);

  responses.push({success:true,data:{duplicate:true,behavior:'reference'}});
  await verify(form,button);
  assert.match(feedback.textContent,/تأیید نشد/);
  assert.equal(navigated.length,1);

  responses.push({success:true,data:{duplicate:true,edit_url:'https://evil.example/jihadi/?afe_tracking=SECRET'}});
  await verify(form,button);
  assert.equal(navigated.length,1,'Cross-origin redirect must be rejected');

  responses.push({success:true,data:{duplicate:false,edit_url:'https://example.test/jihadi/?afe_tracking=SECRET'}});
  await verify(form,button);
  assert.equal(navigated.length,1,'Only a confirmed duplicate response may redirect');

  responses.push(new Error('network'));
  await verify(form,button);
  assert.match(feedback.textContent,/دوباره تلاش/);
  assert.doesNotMatch(feedback.textContent,/network/);

  assert.ok(source.includes("const recoverButton = e.target.closest('.afe-duplicate-recovery__submit')"),
    'Delegate recovery clicks from the form');
  assert.ok(renderer.includes('data-afe-duplicate-tracking'),
    'Only show inline recovery when tracking editing mode is enabled');
  assert.ok(css.includes('.afe-form .afe-duplicate-recovery__controls'),
    'Recovery input needs inline responsive styling');
  assert.ok(!source.includes('afe-draft-resume-jump'), 'Old inert jump UX should be removed');
  console.log('PASS duplicate reference: inline credential validation, only authorized same-origin redirect, privacy, and inline UI');
})().catch(error=>{console.error(error);process.exitCode=1;});
