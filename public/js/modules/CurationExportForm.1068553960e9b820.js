"use strict";(self.webpackChunk=self.webpackChunk||[]).push([[870],{3670:(t,e,r)=>{r.r(e),r.d(e,{default:()=>c});var n=r(5353);function o(t){return o="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},o(t)}function a(t,e){var r=Object.keys(t);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(t);e&&(n=n.filter((function(e){return Object.getOwnPropertyDescriptor(t,e).enumerable}))),r.push.apply(r,n)}return r}function s(t){for(var e=1;e<arguments.length;e++){var r=null!=arguments[e]?arguments[e]:{};e%2?a(Object(r),!0).forEach((function(e){i(t,e,r[e])})):Object.getOwnPropertyDescriptors?Object.defineProperties(t,Object.getOwnPropertyDescriptors(r)):a(Object(r)).forEach((function(e){Object.defineProperty(t,e,Object.getOwnPropertyDescriptor(r,e))}))}return t}function i(t,e,r){var n;return n=function(t,e){if("object"!=o(t)||!t)return t;var r=t[Symbol.toPrimitive];if(void 0!==r){var n=r.call(t,e||"default");if("object"!=o(n))return n;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===e?String:Number)(t)}(e,"string"),(e="symbol"==o(n)?n:String(n))in t?Object.defineProperty(t,e,{value:r,enumerable:!0,configurable:!0,writable:!0}):t[e]=r,t}const l={components:{},data:function(){return{}},computed:s({},(0,n.L8)("panels",{panels:"Items"})),methods:s({},(0,n.i0)("panels",{getAllPanels:"getAllItems"})),mounted:function(){this.getAllPanels()}};const c=(0,r(4486).A)(l,(function(){var t=this,e=t.$createElement,r=t._self._c||e;return r("div",{staticClass:"component-container"},[r("div",{staticClass:"card w-50"},[r("div",{staticClass:"card-header"},[t._v("\n            Export Curation Data\n        ")]),t._v(" "),r("div",{staticClass:"card-body"},[r("form",{attrs:{action:"/curations/export",method:"GET"}},[r("div",{staticClass:"form-group"},[r("label",{attrs:{for:"expert_panel_id"}},[t._v("Expert Panel:")]),t._v(" "),r("select",{staticClass:"form-control",attrs:{name:"expert_panel_id",id:"expert_panel_id"}},[r("option",{attrs:{value:""}},[t._v("All")]),t._v(" "),t._l(t.panels,(function(e){return r("option",{key:e.id,domProps:{value:e.id}},[t._v(t._s(e.name))])}))],2)]),t._v(" "),t._m(0),t._v(" "),t._m(1)])])])])}),[function(){var t=this,e=t.$createElement,r=t._self._c||e;return r("div",{staticClass:"form-group row"},[r("div",{staticClass:"col-sm-6"},[r("label",{attrs:{for:"start_date"}},[t._v("Start Date")]),t._v(" "),r("input",{staticClass:"form-control",attrs:{type:"date",name:"start_date",id:"start_date"}})]),t._v(" "),r("div",{staticClass:"col-sm-6"},[r("label",{attrs:{for:"end_date"}},[t._v("End Date")]),t._v(" "),r("input",{staticClass:"form-control",attrs:{type:"date",name:"end_date",id:"end_date"}})])])},function(){var t=this,e=t.$createElement,r=t._self._c||e;return r("div",{staticClass:"form-group"},[r("button",{staticClass:"btn btn-primary",attrs:{type:"submit"}},[t._v("Download Export")])])}],!1,null,null,null).exports}}]);
//# sourceMappingURL=CurationExportForm.1068553960e9b820.js.map