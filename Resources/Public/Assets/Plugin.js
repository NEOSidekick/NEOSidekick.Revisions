/*! For license information please see Plugin.js.LICENSE.txt */
!function(e){var t={};function __webpack_require__(r){if(t[r])return t[r].exports;var n=t[r]={i:r,l:!1,exports:{}};return e[r].call(n.exports,n,n.exports,__webpack_require__),n.l=!0,n.exports}__webpack_require__.m=e,__webpack_require__.c=t,__webpack_require__.d=function(e,t,r){__webpack_require__.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},__webpack_require__.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},__webpack_require__.t=function(e,t){if(1&t&&(e=__webpack_require__(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(__webpack_require__.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var n in e)__webpack_require__.d(r,n,function(t){return e[t]}.bind(null,n));return r},__webpack_require__.n=function(e){var t=e&&e.__esModule?function getDefault(){return e.default}:function getModuleExports(){return e};return __webpack_require__.d(t,"a",t),t},__webpack_require__.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},__webpack_require__.p="",__webpack_require__(__webpack_require__.s=11)}([function(e,t,r){"use strict";var n=function _interopRequireDefault(e){return e&&e.__esModule?e:{default:e}}(r(2));e.exports=(0,n.default)("vendor")().React},function(e,t,r){"use strict";var n=function _interopRequireDefault(e){return e&&e.__esModule?e:{default:e}}(r(2));e.exports=(0,n.default)("NeosProjectPackages")().ReactUiComponents},function(e,t,r){"use strict";t.__esModule=!0;var n=r(4);t.default=function readFromConsumerApi(e){return function(){for(var t,r=[],a=0;a<arguments.length;a++)r[a]=arguments[a];if(window["@Neos:HostPluginAPI"]&&window["@Neos:HostPluginAPI"]["@"+e])return(t=window["@Neos:HostPluginAPI"])["@"+e].apply(t,n.__spread(r));throw new Error("You are trying to read from a consumer api that hasn't been initialized yet!")}}},function(e,t,r){"use strict";var n=function _interopRequireDefault(e){return e&&e.__esModule?e:{default:e}}(r(2));e.exports=(0,n.default)("vendor")().PropTypes},function(e,t,r){"use strict";r.r(t),r.d(t,"__extends",(function(){return __extends})),r.d(t,"__assign",(function(){return __assign})),r.d(t,"__rest",(function(){return __rest})),r.d(t,"__decorate",(function(){return __decorate})),r.d(t,"__param",(function(){return __param})),r.d(t,"__metadata",(function(){return __metadata})),r.d(t,"__awaiter",(function(){return __awaiter})),r.d(t,"__generator",(function(){return __generator})),r.d(t,"__createBinding",(function(){return __createBinding})),r.d(t,"__exportStar",(function(){return __exportStar})),r.d(t,"__values",(function(){return __values})),r.d(t,"__read",(function(){return __read})),r.d(t,"__spread",(function(){return __spread})),r.d(t,"__spreadArrays",(function(){return __spreadArrays})),r.d(t,"__await",(function(){return __await})),r.d(t,"__asyncGenerator",(function(){return __asyncGenerator})),r.d(t,"__asyncDelegator",(function(){return __asyncDelegator})),r.d(t,"__asyncValues",(function(){return __asyncValues})),r.d(t,"__makeTemplateObject",(function(){return __makeTemplateObject})),r.d(t,"__importStar",(function(){return __importStar})),r.d(t,"__importDefault",(function(){return __importDefault})),r.d(t,"__classPrivateFieldGet",(function(){return __classPrivateFieldGet})),r.d(t,"__classPrivateFieldSet",(function(){return __classPrivateFieldSet}));var extendStatics=function(e,t){return(extendStatics=Object.setPrototypeOf||{__proto__:[]}instanceof Array&&function(e,t){e.__proto__=t}||function(e,t){for(var r in t)t.hasOwnProperty(r)&&(e[r]=t[r])})(e,t)};function __extends(e,t){function __(){this.constructor=e}extendStatics(e,t),e.prototype=null===t?Object.create(t):(__.prototype=t.prototype,new __)}var __assign=function(){return(__assign=Object.assign||function __assign(e){for(var t,r=1,n=arguments.length;r<n;r++)for(var a in t=arguments[r])Object.prototype.hasOwnProperty.call(t,a)&&(e[a]=t[a]);return e}).apply(this,arguments)};function __rest(e,t){var r={};for(var n in e)Object.prototype.hasOwnProperty.call(e,n)&&t.indexOf(n)<0&&(r[n]=e[n]);if(null!=e&&"function"==typeof Object.getOwnPropertySymbols){var a=0;for(n=Object.getOwnPropertySymbols(e);a<n.length;a++)t.indexOf(n[a])<0&&Object.prototype.propertyIsEnumerable.call(e,n[a])&&(r[n[a]]=e[n[a]])}return r}function __decorate(e,t,r,n){var a,o=arguments.length,i=o<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,r):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,r,n);else for(var l=e.length-1;l>=0;l--)(a=e[l])&&(i=(o<3?a(i):o>3?a(t,r,i):a(t,r))||i);return o>3&&i&&Object.defineProperty(t,r,i),i}function __param(e,t){return function(r,n){t(r,n,e)}}function __metadata(e,t){if("object"==typeof Reflect&&"function"==typeof Reflect.metadata)return Reflect.metadata(e,t)}function __awaiter(e,t,r,n){return new(r||(r=Promise))((function(a,o){function fulfilled(e){try{step(n.next(e))}catch(e){o(e)}}function rejected(e){try{step(n.throw(e))}catch(e){o(e)}}function step(e){e.done?a(e.value):function adopt(e){return e instanceof r?e:new r((function(t){t(e)}))}(e.value).then(fulfilled,rejected)}step((n=n.apply(e,t||[])).next())}))}function __generator(e,t){var r,n,a,o,i={label:0,sent:function(){if(1&a[0])throw a[1];return a[1]},trys:[],ops:[]};return o={next:verb(0),throw:verb(1),return:verb(2)},"function"==typeof Symbol&&(o[Symbol.iterator]=function(){return this}),o;function verb(o){return function(l){return function step(o){if(r)throw new TypeError("Generator is already executing.");for(;i;)try{if(r=1,n&&(a=2&o[0]?n.return:o[0]?n.throw||((a=n.return)&&a.call(n),0):n.next)&&!(a=a.call(n,o[1])).done)return a;switch(n=0,a&&(o=[2&o[0],a.value]),o[0]){case 0:case 1:a=o;break;case 4:return i.label++,{value:o[1],done:!1};case 5:i.label++,n=o[1],o=[0];continue;case 7:o=i.ops.pop(),i.trys.pop();continue;default:if(!(a=i.trys,(a=a.length>0&&a[a.length-1])||6!==o[0]&&2!==o[0])){i=0;continue}if(3===o[0]&&(!a||o[1]>a[0]&&o[1]<a[3])){i.label=o[1];break}if(6===o[0]&&i.label<a[1]){i.label=a[1],a=o;break}if(a&&i.label<a[2]){i.label=a[2],i.ops.push(o);break}a[2]&&i.ops.pop(),i.trys.pop();continue}o=t.call(e,i)}catch(e){o=[6,e],n=0}finally{r=a=0}if(5&o[0])throw o[1];return{value:o[0]?o[1]:void 0,done:!0}}([o,l])}}}function __createBinding(e,t,r,n){void 0===n&&(n=r),e[n]=t[r]}function __exportStar(e,t){for(var r in e)"default"===r||t.hasOwnProperty(r)||(t[r]=e[r])}function __values(e){var t="function"==typeof Symbol&&Symbol.iterator,r=t&&e[t],n=0;if(r)return r.call(e);if(e&&"number"==typeof e.length)return{next:function(){return e&&n>=e.length&&(e=void 0),{value:e&&e[n++],done:!e}}};throw new TypeError(t?"Object is not iterable.":"Symbol.iterator is not defined.")}function __read(e,t){var r="function"==typeof Symbol&&e[Symbol.iterator];if(!r)return e;var n,a,o=r.call(e),i=[];try{for(;(void 0===t||t-- >0)&&!(n=o.next()).done;)i.push(n.value)}catch(e){a={error:e}}finally{try{n&&!n.done&&(r=o.return)&&r.call(o)}finally{if(a)throw a.error}}return i}function __spread(){for(var e=[],t=0;t<arguments.length;t++)e=e.concat(__read(arguments[t]));return e}function __spreadArrays(){for(var e=0,t=0,r=arguments.length;t<r;t++)e+=arguments[t].length;var n=Array(e),a=0;for(t=0;t<r;t++)for(var o=arguments[t],i=0,l=o.length;i<l;i++,a++)n[a]=o[i];return n}function __await(e){return this instanceof __await?(this.v=e,this):new __await(e)}function __asyncGenerator(e,t,r){if(!Symbol.asyncIterator)throw new TypeError("Symbol.asyncIterator is not defined.");var n,a=r.apply(e,t||[]),o=[];return n={},verb("next"),verb("throw"),verb("return"),n[Symbol.asyncIterator]=function(){return this},n;function verb(e){a[e]&&(n[e]=function(t){return new Promise((function(r,n){o.push([e,t,r,n])>1||resume(e,t)}))})}function resume(e,t){try{!function step(e){e.value instanceof __await?Promise.resolve(e.value.v).then(fulfill,reject):settle(o[0][2],e)}(a[e](t))}catch(e){settle(o[0][3],e)}}function fulfill(e){resume("next",e)}function reject(e){resume("throw",e)}function settle(e,t){e(t),o.shift(),o.length&&resume(o[0][0],o[0][1])}}function __asyncDelegator(e){var t,r;return t={},verb("next"),verb("throw",(function(e){throw e})),verb("return"),t[Symbol.iterator]=function(){return this},t;function verb(n,a){t[n]=e[n]?function(t){return(r=!r)?{value:__await(e[n](t)),done:"return"===n}:a?a(t):t}:a}}function __asyncValues(e){if(!Symbol.asyncIterator)throw new TypeError("Symbol.asyncIterator is not defined.");var t,r=e[Symbol.asyncIterator];return r?r.call(e):(e=__values(e),t={},verb("next"),verb("throw"),verb("return"),t[Symbol.asyncIterator]=function(){return this},t);function verb(r){t[r]=e[r]&&function(t){return new Promise((function(n,a){(function settle(e,t,r,n){Promise.resolve(n).then((function(t){e({value:t,done:r})}),t)})(n,a,(t=e[r](t)).done,t.value)}))}}}function __makeTemplateObject(e,t){return Object.defineProperty?Object.defineProperty(e,"raw",{value:t}):e.raw=t,e}function __importStar(e){if(e&&e.__esModule)return e;var t={};if(null!=e)for(var r in e)Object.hasOwnProperty.call(e,r)&&(t[r]=e[r]);return t.default=e,t}function __importDefault(e){return e&&e.__esModule?e:{default:e}}function __classPrivateFieldGet(e,t){if(!t.has(e))throw new TypeError("attempted to get private field on non-instance");return t.get(e)}function __classPrivateFieldSet(e,t,r){if(!t.has(e))throw new TypeError("attempted to set private field on non-instance");return t.set(e,r),r}},function(e,t,r){"use strict";var n=function _interopRequireDefault(e){return e&&e.__esModule?e:{default:e}}(r(2));e.exports=(0,n.default)("NeosProjectPackages")().NeosUiReduxStore},function(e,t,r){"use strict";var n=function _interopRequireDefault(e){return e&&e.__esModule?e:{default:e}}(r(2));e.exports=(0,n.default)("vendor")().reactRedux},function(e,t,r){"use strict";var n=function _interopRequireDefault(e){return e&&e.__esModule?e:{default:e}}(r(2));e.exports=(0,n.default)("vendor")().plow},function(e,t,r){"use strict";t.__esModule=!0;var n=r(4),a=n.__importDefault(r(17)),o=n.__importDefault(r(18)),i=function(e){function SynchronousRegistry(t){var r=e.call(this,t)||this;return r._registry=[],r}return n.__extends(SynchronousRegistry,e),SynchronousRegistry.prototype.set=function(e,t,r){if(void 0===r&&(r=0),"string"!=typeof e)throw new Error("Key must be a string");if("string"!=typeof r&&"number"!=typeof r)throw new Error("Position must be a string or a number");var n={key:e,value:t};r&&(n.position=r);var a=this._registry.findIndex((function(t){return t.key===e}));return-1===a?this._registry.push(n):this._registry[a]=n,t},SynchronousRegistry.prototype.get=function(e){if("string"!=typeof e)return console.error("Key must be a string"),null;var t=this._registry.find((function(t){return t.key===e}));return t?t.value:null},SynchronousRegistry.prototype._getChildrenWrapped=function(e){var t=this._registry.filter((function(t){return 0===t.key.indexOf(e+"/")}));return o.default(t)},SynchronousRegistry.prototype.getChildrenAsObject=function(e){var t={};return this._getChildrenWrapped(e).forEach((function(e){t[e.key]=e.value})),t},SynchronousRegistry.prototype.getChildren=function(e){return this._getChildrenWrapped(e).map((function(e){return e.value}))},SynchronousRegistry.prototype.has=function(e){return"string"!=typeof e?(console.error("Key must be a string"),!1):Boolean(this._registry.find((function(t){return t.key===e})))},SynchronousRegistry.prototype._getAllWrapped=function(){return o.default(this._registry)},SynchronousRegistry.prototype.getAllAsObject=function(){var e={};return this._getAllWrapped().forEach((function(t){e[t.key]=t.value})),e},SynchronousRegistry.prototype.getAllAsList=function(){return this._getAllWrapped().map((function(e){return Object.assign({id:e.key},e.value)}))},SynchronousRegistry}(a.default);t.default=i},function(e,t,r){"use strict";var n=function _interopRequireDefault(e){return e&&e.__esModule?e:{default:e}}(r(2));e.exports=(0,n.default)("NeosProjectPackages")().NeosUiDecorators},function(e,t,r){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.fetchWithErrorHandling=void 0;var n=function _interopRequireDefault(e){return e&&e.__esModule?e:{default:e}}(r(2));t.default=(0,n.default)("NeosProjectPackages")().NeosUiBackendConnectorDefault;var a=(0,n.default)("NeosProjectPackages")().NeosUiBackendConnector.fetchWithErrorHandling;t.fetchWithErrorHandling=a},function(e,t,r){"use strict";r(12)},function(e,t,r){"use strict";var n=_interopRequireDefault(r(13)),a=_interopRequireDefault(r(20));function _interopRequireDefault(e){return e&&e.__esModule?e:{default:e}}(0,n.default)("CodeQ:Revisions",{},(function(e){e.get("inspector").get("views").set("CodeQ.Revisions/Inspector/Views/RevisionsView",{component:a.default})}))},function(e,t,r){"use strict";t.__esModule=!0;var n=r(4),a=n.__importDefault(r(14));t.createConsumerApi=a.default;var o=n.__importDefault(r(2));t.readFromConsumerApi=o.default;var i=r(16);t.SynchronousRegistry=i.SynchronousRegistry,t.SynchronousMetaRegistry=i.SynchronousMetaRegistry,t.default=o.default("manifest")},function(e,t,r){"use strict";t.__esModule=!0;var n=r(4).__importDefault(r(15)),a=function createReadOnlyValue(e){return{value:e,writable:!1,enumerable:!1,configurable:!0}};t.default=function createConsumerApi(e,t){var r={};Object.keys(t).forEach((function(e){Object.defineProperty(r,e,a(t[e]))})),Object.defineProperty(r,"@manifest",a(n.default(e))),Object.defineProperty(window,"@Neos:HostPluginAPI",a(r))}},function(e,t,r){"use strict";t.__esModule=!0,t.default=function(e){return function(t,r,n){var a;e.push(((a={})[t]={options:r,bootstrap:n},a))}}},function(e,t,r){"use strict";t.__esModule=!0;var n=r(4),a=n.__importDefault(r(8));t.SynchronousRegistry=a.default;var o=n.__importDefault(r(19));t.SynchronousMetaRegistry=o.default},function(e,t,r){"use strict";t.__esModule=!0;var n=function n(e){this.SERIAL_VERSION_UID="d8a5aa78-978e-11e6-ae22-56b6b6499611",this.description=e};t.default=n},function(e,t,r){"use strict";t.__esModule=!0;var n=r(4);t.default=function positionalArraySorter(e,t,r){var a,o,i,l,s,c,u,f,d,m,p,_,y,v;void 0===t&&(t="position"),void 0===r&&(r="key");var h="string"==typeof t?function(e){return e[t]}:t,g={},b={},E={},w={},R={},S={};e.forEach((function(e,t){var n=e[r]?e[r]:String(t);g[n]=t;var a=h(e),o=String(a||t),i=!1;if(o.startsWith("start")){var l=(s=o.match(/start\s+(\d+)/))&&s[1]?Number(s[1]):0;E[l]||(E[l]=[]),E[l].push(n)}else if(o.startsWith("end")){var s;l=(s=o.match(/end\s+(\d+)/))&&s[1]?Number(s[1]):0;w[l]||(w[l]=[]),w[l].push(n)}else if(o.startsWith("before")){if(u=o.match(/before\s+(\S+)(\s+(\d+))?/)){var c=u[1];l=u[3]?Number(u[3]):0;R[c]||(R[c]={}),R[c][l]||(R[c][l]=[]),R[c][l].push(n)}else i=!0}else if(o.startsWith("after")){var u;if(u=o.match(/after\s+(\S+)(\s+(\d+))?/)){c=u[1],l=u[3]?Number(u[3]):0;S[c]||(S[c]={}),S[c][l]||(S[c][l]=[]),S[c][l].push(n)}else i=!0}else i=!0;if(i){var f=parseFloat(o);!isNaN(f)&&isFinite(f)||(f=t),b[f]||(b[f]=[]),b[f].push(n)}}));var D=[],j=[],O=[],x=[],k=function sortedWeights(e,t){var r=Object.keys(e).map((function(e){return Number(e)})).sort((function(e,t){return e-t}));return t?r:r.reverse()},C=function addToResults(e,t){e.forEach((function(e){var r,a,o,i;if(!(x.indexOf(e)>=0)){if(x.push(e),R[e]){var l=k(R[e],!0);try{for(var s=n.__values(l),c=s.next();!c.done;c=s.next()){var u=c.value;addToResults(R[e][u],t)}}catch(e){r={error:e}}finally{try{c&&!c.done&&(a=s.return)&&a.call(s)}finally{if(r)throw r.error}}}if(t.push(e),S[e]){var f=k(S[e],!1);try{for(var d=n.__values(f),m=d.next();!m.done;m=d.next()){u=m.value;addToResults(S[e][u],t)}}catch(e){o={error:e}}finally{try{m&&!m.done&&(i=d.return)&&i.call(d)}finally{if(o)throw o.error}}}}}))};try{for(var P=n.__values(k(E,!1)),M=P.next();!M.done;M=P.next()){var A=M.value;C(E[A],D)}}catch(e){a={error:e}}finally{try{M&&!M.done&&(o=P.return)&&o.call(P)}finally{if(a)throw a.error}}try{for(var I=n.__values(k(b,!0)),B=I.next();!B.done;B=I.next()){A=B.value;C(b[A],j)}}catch(e){i={error:e}}finally{try{B&&!B.done&&(l=I.return)&&l.call(I)}finally{if(i)throw i.error}}try{for(var N=n.__values(k(w,!0)),T=N.next();!T.done;T=N.next()){A=T.value;C(w[A],O)}}catch(e){s={error:e}}finally{try{T&&!T.done&&(c=N.return)&&c.call(N)}finally{if(s)throw s.error}}try{for(var q=n.__values(Object.keys(R)),F=q.next();!F.done;F=q.next()){var U=F.value;if(!(x.indexOf(U)>=0))try{for(var W=(d=void 0,n.__values(k(R[U],!1))),L=W.next();!L.done;L=W.next()){A=L.value;C(R[U][A],D)}}catch(e){d={error:e}}finally{try{L&&!L.done&&(m=W.return)&&m.call(W)}finally{if(d)throw d.error}}}}catch(e){u={error:e}}finally{try{F&&!F.done&&(f=q.return)&&f.call(q)}finally{if(u)throw u.error}}try{for(var H=n.__values(Object.keys(S)),V=H.next();!V.done;V=H.next()){U=V.value;if(!(x.indexOf(U)>=0))try{for(var G=(y=void 0,n.__values(k(S[U],!1))),$=G.next();!$.done;$=G.next()){A=$.value;C(S[U][A],j)}}catch(e){y={error:e}}finally{try{$&&!$.done&&(v=G.return)&&v.call(G)}finally{if(y)throw y.error}}}}catch(e){p={error:e}}finally{try{V&&!V.done&&(_=H.return)&&_.call(H)}finally{if(p)throw p.error}}return n.__spread(D,j,O).map((function(e){return g[e]})).map((function(t){return e[t]}))}},function(e,t,r){"use strict";t.__esModule=!0;var n=r(4),a=function(e){function SynchronousMetaRegistry(){return null!==e&&e.apply(this,arguments)||this}return n.__extends(SynchronousMetaRegistry,e),SynchronousMetaRegistry.prototype.set=function(t,r){if("d8a5aa78-978e-11e6-ae22-56b6b6499611"!==r.SERIAL_VERSION_UID)throw new Error("You can only add registries to a meta registry");return e.prototype.set.call(this,t,r)},SynchronousMetaRegistry}(n.__importDefault(r(8)).default);t.default=a},function(e,t,r){"use strict";r.r(t);var n=r(0),a=r.n(n),o=r(3),i=r.n(o),l=r(6),s=r(7),c=r(5),u=r(9),f=r(1),d=r(10);class ApplyError extends Error{constructor(e,t,r){super(e),this.name="ApplyError",this._status=t,this._conflicts=r}get status(){return this._status}get conflicts(){return this._conflicts}}function fetchFromBackend(e,t){t(!0);let r=`/neos/codeq/revisions/${e.action}?`;return e.params.node&&(r+="&node="+encodeURIComponent(e.params.node.contextPath)),e.params.revision&&(r+="&revision="+encodeURIComponent(e.params.revision.identifier)),e.params.label&&(r+="&label="+encodeURIComponent(e.params.label)),e.params.force&&(r+="&force="+encodeURIComponent(e.params.force)),d.fetchWithErrorHandling.withCsrfToken(t=>({url:r,method:"get"===e.action?"GET":"POST",credentials:"include",headers:{"X-Flow-Csrftoken":t,"Content-Type":"application/json"}})).then(async t=>{if(t){if(t.status>=400&&t.status<600){const{message:r}=t;if("apply"===e.action){let e=[];try{e=await t.json()}catch(e){}throw new ApplyError(r,t.status,e)}throw new Error(r)}return t.json()}}).finally(()=>{t(!1)})}function formatRevisionDate(e){return formatChangeDate(e.creationDateTime)}function formatChangeDate(e){return new Date(e).toLocaleString(void 0,{year:"2-digit",month:"2-digit",day:"2-digit",hour:"2-digit",minute:"2-digit"})}var m=a.a.memo(({revision:e,onUpdate:t,onClose:r,translate:o,isLoading:i})=>{const[l,s]=Object(n.useState)(e.label||"");return a.a.createElement("div",null,a.a.createElement("table",{style:{width:"100%"}},a.a.createElement("tbody",null,a.a.createElement("tr",null,a.a.createElement("td",{style:{verticalAlign:"top"}},a.a.createElement("strong",null,o("header.revision"))),a.a.createElement("td",null,formatRevisionDate(e))),a.a.createElement("tr",null,a.a.createElement("td",{style:{verticalAlign:"top"}},a.a.createElement("strong",null,o("header.creator"))),a.a.createElement("td",null,e.creator)),e.isMoved&&a.a.createElement("tr",null,a.a.createElement("td",{style:{verticalAlign:"top"}},a.a.createElement("strong",null,o("header.moved"))),a.a.createElement("td",null,o("revision.isMoved"))),a.a.createElement("tr",null,a.a.createElement("td",{style:{verticalAlign:"top"}},a.a.createElement("strong",null,o("header.identifier"))),a.a.createElement("td",null,e.identifier)),a.a.createElement("tr",null,a.a.createElement("td",{colSpan:2},a.a.createElement("strong",null,o("header.label")))),a.a.createElement("tr",null,a.a.createElement("td",{colSpan:2},a.a.createElement(f.TextInput,{defaultValue:l,onChange:s}))))),a.a.createElement("div",{style:{marginTop:"1rem"}},a.a.createElement(f.Button,{style:"warn",onClick:r,disabled:i},o("action.close")),a.a.createElement(f.Button,{style:"success",onClick:()=>t(l),disabled:l==e.label||i},o("action.apply"))))});var p=a.a.memo(({original:e,changed:t})=>a.a.createElement("tr",null,a.a.createElement("td",null,e&&a.a.createElement("del",null,a.a.createElement("a",{href:e.resource.uri,target:"_blank"},e.resource.filename))),a.a.createElement("td",null,t&&a.a.createElement("ins",null,a.a.createElement("a",{href:t.resource.uri,target:"_blank"},t.resource.filename)))));var _=a.a.memo(({original:e,changed:t})=>a.a.createElement("tr",null,a.a.createElement("td",null,e&&a.a.createElement("img",{src:e.src,alt:e.alt,title:e.title})),a.a.createElement("td",null,t&&a.a.createElement("img",{src:t.src,alt:t.alt,title:t.title}))));var y=a.a.memo(({original:e,changed:t})=>a.a.createElement("tr",null,a.a.createElement("td",null,a.a.createElement("time",null,formatChangeDate(e))),a.a.createElement("td",null,a.a.createElement("time",null,formatChangeDate(t)))));var v=a.a.memo(({diff:e})=>a.a.createElement(a.a.Fragment,null,e.map(e=>e.map((e,t)=>a.a.createElement("tr",{key:t},a.a.createElement("td",{dangerouslySetInnerHTML:{__html:e.base.lines.join()},style:{color:"#ff460d",textAlign:"left"}}),a.a.createElement("td",{dangerouslySetInnerHTML:{__html:e.changed.lines.join()},style:{color:"#00a338",textAlign:"left"}}))))));var h=a.a.memo(({nodeChanges:e,contentDimensions:t,translate:r})=>{const{node:o,type:i,changes:l=[]}=e,s=Object(n.useMemo)(()=>"changeNode"===i?"#ff8700":"addNode"===i?"#00a338":"#ff460d",[i]),c=Object(n.useMemo)(()=>a.a.createElement(a.a.Fragment,null,Object.keys(o.dimensions).map(e=>{const n=t[e];if(!n)return null;const i=o.dimensions[e][0];return i?a.a.createElement("span",{key:e,title:`${r(n.label)}: ${i}`},n.icon&&a.a.createElement(f.Icon,{icon:n.icon,style:{marginRight:"0.5em",color:"#222"}}),r(n.presets[i].label)):null})),[o.dimensions]);return a.a.createElement("div",{style:{background:"#eee",color:"#252525",marginBottom:"1rem",padding:"1rem 1rem",borderLeft:"3px solid "+s}},a.a.createElement("div",{style:{display:"flex",justifyContent:"space-between"}},a.a.createElement("span",null,a.a.createElement("strong",null,r("diff."+i)),o.nodeType.icon&&a.a.createElement(f.Icon,{icon:o.nodeType.icon,title:o.nodeType.label?r(o.nodeType.label):o.nodeType.name,style:{margin:"0 0.5em",color:"#222"},color:"primaryBlue"}),o.label),a.a.createElement("span",null,c,a.a.createElement(f.Icon,{icon:"clock",style:{margin:"0 0.5em",color:"#222"}}),formatChangeDate(o.lastModificationDateTime))),Object.keys(l).map(e=>{const{propertyLabel:t,type:n,diff:o,original:i,changed:s}=l[e];return a.a.createElement("div",{key:e,style:{borderTop:"1px solid #323232",marginTop:"1rem",padding:"1rem"}},a.a.createElement("div",null,r("diff.propertyLabel",t,{propertyLabel:t})),a.a.createElement("table",{style:{width:"100%",borderSpacing:0}},a.a.createElement("thead",null,a.a.createElement("tr",null,a.a.createElement("th",{style:{textAlign:"left"}},r("diff.old")),a.a.createElement("th",{style:{textAlign:"left"}},r("diff.new")))),a.a.createElement("tbody",null,"text"===n&&a.a.createElement(v,{diff:o}),"image"===n&&a.a.createElement(_,{original:i,changed:s}),"asset"===n&&a.a.createElement(p,{original:i,changed:s}),"datetime"===n&&a.a.createElement(y,{original:i,changed:s}))))}))});class ErrorBoundary_ErrorBoundary extends a.a.Component{constructor(e){super(e),this.state={hasError:!1}}static getDerivedStateFromError(){return{hasError:!0}}componentDidCatch(e,t){console.error(e,t)}render(){return this.state.hasError?a.a.createElement("strong",{style:{color:"red"}},this.props.text||"Something went wrong."):this.props.children}}var g=a.a.memo(({documentNode:e,revision:t,translate:r,applyRevision:o,onClose:i,contentDimensions:l})=>{const[s,c]=Object(n.useState)(!0),[u,d]=Object(n.useState)({}),[m,p]=Object(n.useState)(""),_=Object(n.useMemo)(()=>t?t.label||r("revision.label","By "+t.creator,{creator:t.creator}):"",[t]),y=Object(n.useCallback)(()=>{c(!0),fetchFromBackend({action:"getDiff",params:{node:e,revision:t}},c).then(({diff:e})=>d(e)).catch(e=>{p(r("error.failedFetchingChanges")),console.error(e.message)})},[t]);return Object(n.useEffect)(()=>{y()},[t]),a.a.createElement("div",{style:{padding:"1rem",height:"inherit",display:"flex",flexDirection:"column"}},a.a.createElement("h1",{style:{marginBottom:"2rem",fontSize:"1.5em",lineHeight:1.3}},r("diff.header","The revision contains the following changes",{version:_,date:formatRevisionDate(t)})),t.isMoved&&a.a.createElement("div",{style:{marginBottom:"2rem",fontWeight:"bold"}},a.a.createElement(f.Icon,{icon:"exclamation-triangle",style:{marginRight:"0.5rem",color:"#ff8700"}}),r("diff.moved","The document was moved to another location.")),a.a.createElement("div",{style:{overflow:"auto"}},s?a.a.createElement("div",null,a.a.createElement(f.Icon,{icon:"spinner",spin:!0,color:"primaryBlue"})," Loading …"):u&&Object.keys(u).length>0?Object.keys(u).map(e=>Object.keys(u[e]).map(t=>{var n;return a.a.createElement("div",{key:e,style:{marginBottom:"1rem"}},a.a.createElement(ErrorBoundary_ErrorBoundary,{text:`Diff for node ${(null===(n=u[e][t].node)||void 0===n?void 0:n.label)||e} could not be rendered. Please check the logs.`},a.a.createElement(h,{nodeChanges:u[e][t],translate:r,contentDimensions:l})))})):a.a.createElement("p",null,m||r("diff.empty","No changes have been found"))),a.a.createElement("div",{style:{marginTop:"2rem",display:"flex",justifyContent:"space-between"}},a.a.createElement(f.Button,{style:"warn",onClick:i},r("action.close")),a.a.createElement(f.Button,{style:"success",onClick:()=>o(t),disabled:s||0===Object.keys(u).length},a.a.createElement(f.Icon,{icon:"check"})," ",r("action.apply"))))});var b=a.a.memo(({revision:e,translate:t,showRevision:r,setSelectedRevision:n,deleteRevision:o,showDeleteButton:i,allowApply:l})=>a.a.createElement("tr",{key:e.creationDateTime},a.a.createElement("td",{title:t("tooltip.revisionLabel","Created on {revisionDate} by {creator}",{revisionDate:formatRevisionDate(e),creator:e.creator})},a.a.createElement("div",null,e.label||t("revision.label","By {creator}",{creator:e.creator})),a.a.createElement("time",{style:{opacity:.5}},formatRevisionDate(e))),a.a.createElement("td",{style:{textAlign:"center",verticalAlign:"middle"}},a.a.createElement(f.IconButton,{onClick:()=>r(e),icon:"trash-restore",style:"primary",hoverStyle:"success",size:"small",disabled:!l,title:l?t("action.apply.title","Apply revision {revisionDate} by {creator}",{revisionDate:formatRevisionDate(e),creator:e.creator}):t("action.apply.disabled.title","This revision cannot be applied")}),a.a.createElement(f.IconButton,{onClick:()=>n(e),icon:"comment",style:"primary",hoverStyle:"warn",size:"small",title:t("action.edit.title","Edit revision {revisionDate} by {creator}",{revisionDate:formatRevisionDate(e),creator:e.creator})}),i&&a.a.createElement(f.IconButton,{onClick:()=>o(e),icon:"times-circle",style:"primary",hoverStyle:"error",size:"small",title:t("action.delete.title","Delete revision {revisionDate} by {creator}",{revisionDate:formatRevisionDate(e),creator:e.creator})}))));var E=a.a.memo(({documentNode:e,addFlashMessage:t,reloadDocument:r,i18nRegistry:o,frontendConfiguration:i,renderSecondaryInspector:l,contentDimensions:s})=>{const[c,u]=Object(n.useState)([]),[d,p]=Object(n.useState)(""),[_,y]=Object(n.useState)(null),[v,h]=Object(n.useState)(!0),E=Object(n.useCallback)((e,t="",r=[],n="CodeQ.Revisions",a="Main")=>o.translate(e,t,r,n,a),[]),w=Object(n.useCallback)(()=>{h(!0),fetchFromBackend({action:"get",params:{node:e}},h).then(({revisions:e})=>u(e)).catch(e=>{p(E("error.failedFetchingRevisions")),console.error(e.message)})},[e]),R=Object(n.useCallback)((e,t)=>{confirm(E("error.verifyResolveConflicts","Some conflicts were detected. Do you still want to apply the revision?{conflicts}",{conflicts:"\n\n"+t.join("\n\n")}))&&S(e,!0)},[]),S=Object(n.useCallback)((n,a=!1)=>{fetchFromBackend({action:"apply",params:{node:e,revision:n,force:a}},h).then(()=>{t(E("success.revisionApplied"),E("success.revisionApplied.message",'Revision "{label}" by "{creator}" applied.',{label:n.label||formatRevisionDate(n),creator:n.creator}),"success"),r(),p("")}).catch(e=>{const{status:t,conflicts:r}=e;409===t?R(n,r):(p(E("error.failedApplyingRevision")),console.error(e))})},[]),D=Object(n.useCallback)(e=>{confirm(E("confirm.deleteRevision","Do you really want to delete revision {revisionDate} by {creator}?",{revisionDate:formatRevisionDate(e),creator:e.creator}))&&fetchFromBackend({action:"delete",params:{revision:e}},h).then(()=>{t(E("success.revisionDeleted"),E("success.revisionDeleted.message",'Revision "{label}" by "{creator} deleted.',{label:e.label||formatRevisionDate(e),creator:e.creator}),"success"),p(""),w()}).catch(e=>{p(E("error.failedDeletingRevision")),console.error(e.message)})},[]),j=Object(n.useCallback)(e=>{fetchFromBackend({action:"setlabel",params:{revision:_,label:e}},h).then(()=>{t(E("success.revisionUpdated"),E("success.revisionUpdated.message",'Revision {label} by "{creator}" updated.',{label:_.label||formatRevisionDate(_),creator:_.creator}),"success"),y(null),w()}).catch(e=>{p(E("error.failedUpdatingRevision")),console.error(e.message)})},[_]),O=Object(n.useCallback)(t=>{t?l("REVISIONS_COMPARE",()=>a.a.createElement(g,{documentNode:e,translate:E,revision:t,onClose:O,applyRevision:S,contentDimensions:s})):l(null,null)},[]);return Object(n.useEffect)(w,[e]),a.a.createElement("div",null,d&&a.a.createElement("div",{style:{color:"red",margin:"1rem 0"},role:"alert"},d),v&&a.a.createElement("div",null,a.a.createElement(f.Icon,{icon:"spinner",spin:!0,color:"primaryBlue"})," Loading …"),_?a.a.createElement(m,{revision:_,onUpdate:j,onClose:()=>y(null),translate:E,isLoading:v}):c.length?a.a.createElement("table",{style:{width:"100%"}},a.a.createElement("thead",null,a.a.createElement("tr",null,a.a.createElement("th",{style:{textAlign:"left"}},E("header.label")),a.a.createElement("th",{style:{width:"100px"}},E("header.actions")))),a.a.createElement("tbody",null,c.map((e,t)=>a.a.createElement(a.a.Fragment,{key:t},a.a.createElement(b,{allowApply:t>0&&!e.isEmpty,revision:e,translate:E,showDeleteButton:i.showDeleteButton,deleteRevision:D,setSelectedRevision:y,showRevision:O}),t<c.length-1&&a.a.createElement("tr",null,a.a.createElement("td",{colSpan:2,style:{borderBottom:"1px solid #3f3f3f"}})))))):v?"":a.a.createElement("em",null,E("list.noRevisionsFound")))}),__decorate=function(e,t,r,n){var a,o=arguments.length,i=o<3?t:null===n?n=Object.getOwnPropertyDescriptor(t,r):n;if("object"==typeof Reflect&&"function"==typeof Reflect.decorate)i=Reflect.decorate(e,t,r,n);else for(var l=e.length-1;l>=0;l--)(a=e[l])&&(i=(o<3?a(i):o>3?a(t,r,i):a(t,r))||i);return o>3&&i&&Object.defineProperty(t,r,i),i};let w=class RevisionsView extends n.PureComponent{constructor(e){super(e)}render(){const e=this.props.getNodeByContextPath(this.props.documentNodePath),{addFlashMessage:t,reloadDocument:r,i18nRegistry:n,frontendConfiguration:o,renderSecondaryInspector:i,contentDimensions:l}=this.props;return a.a.createElement(E,{documentNode:e,addFlashMessage:t,reloadDocument:r,i18nRegistry:n,frontendConfiguration:o,renderSecondaryInspector:i,contentDimensions:l})}};w.propTypes={documentNodePath:i.a.string.isRequired,i18nRegistry:i.a.object.isRequired,getNodeByContextPath:i.a.func.isRequired,addFlashMessage:i.a.func.isRequired,reloadDocument:i.a.func.isRequired,frontendConfiguration:i.a.object.isRequired,renderSecondaryInspector:i.a.func.isRequired},w=__decorate([Object(l.connect)(e=>({getNodeByContextPath:c.selectors.CR.Nodes.nodeByContextPath(e)})),Object(l.connect)(Object(s.$transform)({documentNodePath:Object(s.$get)("cr.nodes.documentNode"),contentDimensions:c.selectors.CR.ContentDimensions.byName,allowedPresets:c.selectors.CR.ContentDimensions.allowedPresets,activePresets:c.selectors.CR.ContentDimensions.activePresets}),{addFlashMessage:c.actions.UI.FlashMessages.add,reloadDocument:c.actions.UI.ContentCanvas.reload}),Object(u.neos)(e=>({i18nRegistry:e.get("i18n"),frontendConfiguration:e.get("frontendConfiguration").get("CodeQ.Revisions")}))],w);t.default=w}]);
//# sourceMappingURL=Plugin.js.map