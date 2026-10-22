'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const script=fs.readFileSync(path.join(__dirname,'../assets/js/admin-sms-test.js'),'utf8');
const keys=['general','sms','debug','style','roles','forms'];
let loaded=()=>{};
const tabs=keys.map(key=>({
  dataset:{afeSettingsTab:key}, tabIndex:-1, attributes:{}, focused:false,handlers:{},
  classList:{toggle(){}},
  setAttribute(key,value){this.attributes[key]=value;},
  focus(){this.focused=true;},
  addEventListener(event,handler){this.handlers[event]=handler;}
}));
const panels=keys.map(key=>({dataset:{afeSettingsPanel:key},hidden:false}));
const selector=Object.create(null);
const current={value:'sms'};
const nav={querySelectorAll(){return tabs;}};
selector['[data-afe-settings-tabs]']=nav;
selector['[data-afe-current-tab]']=current;
const form={
  querySelector(selector){return selector in selectors ? selectors[selector] : null;},
  querySelectorAll(){return panels;},
};
const selectors=selector;
const sandbox={
  window:{},
  document:{
    addEventListener(ev,cb){if(ev==='DOMContentLoaded')loaded=cb;},
    querySelector(sel){return sel==='[data-afe-settings-form]'?form:null;}
  }
};
vm.createContext(sandbox);
vm.runInContext(script,sandbox);
loaded();
assert.equal(current.value,'sms');
assert.equal(panels.filter(p=>!p.hidden).length,1);
assert.equal(panels[1].hidden,false);
tabs[2].handlers.click();
assert.equal(current.value,'debug');
assert.equal(panels.filter(p=>!p.hidden).length,1);
assert.equal(panels[2].hidden,false);
assert.equal(tabs[2].attributes['aria-selected'],'true');
let prevented=false;
tabs[2].handlers.keydown({key:'ArrowLeft',preventDefault(){prevented=true;}});
assert.equal(prevented,true);
assert.equal(current.value,'style');
assert.equal(tabs[3].focused,true);
tabs[3].handlers.keydown({key:'Home',preventDefault(){}});
assert.equal(current.value,'general');
tabs[0].handlers.keydown({key:'End',preventDefault(){}});
assert.equal(current.value,'forms');
assert.match(script,/afe_sms_debug/);
assert.match(script,/afe_sms_test/);
assert.match(script,/textContent = entry\[key\]/);
console.log('PASS admin settings: independent panels, persisted active tab, keyboard navigation, safe SMS debug rendering');
