(() => {
  var __create = Object.create;
  var __defProp = Object.defineProperty;
  var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __getProtoOf = Object.getPrototypeOf;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __esm = (fn, res) => function __init() {
    return fn && (res = (0, fn[__getOwnPropNames(fn)[0]])(fn = 0)), res;
  };
  var __commonJS = (cb, mod) => function __require() {
    return mod || (0, cb[__getOwnPropNames(cb)[0]])((mod = { exports: {} }).exports, mod), mod.exports;
  };
  var __copyProps = (to, from, except, desc) => {
    if (from && typeof from === "object" || typeof from === "function") {
      for (let key of __getOwnPropNames(from))
        if (!__hasOwnProp.call(to, key) && key !== except)
          __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
    }
    return to;
  };
  var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(
    // If the importer is in node compatibility mode or this is not an ESM
    // file that has been converted to a CommonJS file using a Babel-
    // compatible transform (i.e. "__esModule" has not been set), then set
    // "default" to the CommonJS "module.exports" for node compatibility.
    isNodeMode || !mod || !mod.__esModule ? __defProp(target, "default", { value: mod, enumerable: true }) : target,
    mod
  ));
  var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);
  var __decorateClass = (decorators, target, key, kind) => {
    var result = kind > 1 ? void 0 : kind ? __getOwnPropDesc(target, key) : target;
    for (var i = decorators.length - 1, decorator; i >= 0; i--)
      if (decorator = decorators[i])
        result = (kind ? decorator(target, key, result) : decorator(result)) || result;
    if (kind && result)
      __defProp(target, key, result);
    return result;
  };

  // node_modules/@neos-project/neos-ui-extensibility/dist/manifest.js
  var init_manifest = __esm({
    "node_modules/@neos-project/neos-ui-extensibility/dist/manifest.js"() {
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/createConsumerApi.js
  var init_createConsumerApi = __esm({
    "node_modules/@neos-project/neos-ui-extensibility/dist/createConsumerApi.js"() {
      init_manifest();
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/readFromConsumerApi.js
  function readFromConsumerApi(key) {
    return (...args) => {
      if (window["@Neos:HostPluginAPI"] && window["@Neos:HostPluginAPI"][`@${key}`]) {
        return window["@Neos:HostPluginAPI"][`@${key}`](...args);
      }
      throw new Error("You are trying to read from a consumer api that hasn't been initialized yet!");
    };
  }
  var init_readFromConsumerApi = __esm({
    "node_modules/@neos-project/neos-ui-extensibility/dist/readFromConsumerApi.js"() {
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/registry/AbstractRegistry.js
  var init_AbstractRegistry = __esm({
    "node_modules/@neos-project/neos-ui-extensibility/dist/registry/AbstractRegistry.js"() {
    }
  });

  // node_modules/@neos-project/positional-array-sorter/dist/positionalArraySorter.js
  var init_positionalArraySorter = __esm({
    "node_modules/@neos-project/positional-array-sorter/dist/positionalArraySorter.js"() {
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/registry/SynchronousRegistry.js
  var init_SynchronousRegistry = __esm({
    "node_modules/@neos-project/neos-ui-extensibility/dist/registry/SynchronousRegistry.js"() {
      init_AbstractRegistry();
      init_positionalArraySorter();
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/registry/SynchronousMetaRegistry.js
  var init_SynchronousMetaRegistry = __esm({
    "node_modules/@neos-project/neos-ui-extensibility/dist/registry/SynchronousMetaRegistry.js"() {
      init_SynchronousRegistry();
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/registry/index.js
  var init_registry = __esm({
    "node_modules/@neos-project/neos-ui-extensibility/dist/registry/index.js"() {
      init_SynchronousRegistry();
      init_SynchronousMetaRegistry();
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/index.js
  var dist_default;
  var init_dist = __esm({
    "node_modules/@neos-project/neos-ui-extensibility/dist/index.js"() {
      init_createConsumerApi();
      init_readFromConsumerApi();
      init_registry();
      dist_default = readFromConsumerApi("manifest");
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/shims/vendor/react/index.js
  var require_react = __commonJS({
    "node_modules/@neos-project/neos-ui-extensibility/dist/shims/vendor/react/index.js"(exports, module) {
      init_readFromConsumerApi();
      module.exports = readFromConsumerApi("vendor")().React;
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/shims/vendor/prop-types/index.js
  var require_prop_types = __commonJS({
    "node_modules/@neos-project/neos-ui-extensibility/dist/shims/vendor/prop-types/index.js"(exports, module) {
      init_readFromConsumerApi();
      module.exports = readFromConsumerApi("vendor")().PropTypes;
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/shims/vendor/react-redux/index.js
  var require_react_redux = __commonJS({
    "node_modules/@neos-project/neos-ui-extensibility/dist/shims/vendor/react-redux/index.js"(exports, module) {
      init_readFromConsumerApi();
      module.exports = readFromConsumerApi("vendor")().reactRedux;
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/shims/vendor/plow-js/index.js
  var require_plow_js = __commonJS({
    "node_modules/@neos-project/neos-ui-extensibility/dist/shims/vendor/plow-js/index.js"(exports, module) {
      init_readFromConsumerApi();
      module.exports = readFromConsumerApi("vendor")().plow;
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/shims/neosProjectPackages/neos-ui-redux-store/index.js
  var require_neos_ui_redux_store = __commonJS({
    "node_modules/@neos-project/neos-ui-extensibility/dist/shims/neosProjectPackages/neos-ui-redux-store/index.js"(exports, module) {
      init_readFromConsumerApi();
      module.exports = readFromConsumerApi("NeosProjectPackages")().NeosUiReduxStore;
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/shims/neosProjectPackages/neos-ui-decorators/index.js
  var require_neos_ui_decorators = __commonJS({
    "node_modules/@neos-project/neos-ui-extensibility/dist/shims/neosProjectPackages/neos-ui-decorators/index.js"(exports, module) {
      init_readFromConsumerApi();
      module.exports = readFromConsumerApi("NeosProjectPackages")().NeosUiDecorators;
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/shims/neosProjectPackages/react-ui-components/index.js
  var require_react_ui_components = __commonJS({
    "node_modules/@neos-project/neos-ui-extensibility/dist/shims/neosProjectPackages/react-ui-components/index.js"(exports, module) {
      init_readFromConsumerApi();
      module.exports = readFromConsumerApi("NeosProjectPackages")().ReactUiComponents;
    }
  });

  // node_modules/@neos-project/neos-ui-extensibility/dist/shims/neosProjectPackages/neos-ui-backend-connector/index.js
  var neos_ui_backend_connector_default, fetchWithErrorHandling;
  var init_neos_ui_backend_connector = __esm({
    "node_modules/@neos-project/neos-ui-extensibility/dist/shims/neosProjectPackages/neos-ui-backend-connector/index.js"() {
      init_readFromConsumerApi();
      neos_ui_backend_connector_default = readFromConsumerApi("NeosProjectPackages")().NeosUiBackendConnectorDefault;
      ({ fetchWithErrorHandling } = readFromConsumerApi("NeosProjectPackages")().NeosUiBackendConnector);
    }
  });

  // src/Api/fetch.ts
  function fetchFromBackend(props, setLoadingState) {
    setLoadingState(true);
    let url = `/neos/neosidekick/revisions/${props.action}?`;
    if (props.params["node"]) {
      url += `&node=${encodeURIComponent(props.params["node"].contextPath)}`;
    }
    if (props.params["revision"]) {
      url += `&revision=${encodeURIComponent(props.params["revision"].identifier)}`;
    }
    if (props.params["label"]) {
      url += `&label=${encodeURIComponent(props.params["label"])}`;
    }
    if (props.params["force"]) {
      url += `&force=${encodeURIComponent(props.params["force"])}`;
    }
    return fetchWithErrorHandling.withCsrfToken((csrfToken) => ({
      url,
      method: props.action === "get" ? "GET" : "POST",
      credentials: "include",
      headers: {
        "X-Flow-Csrftoken": csrfToken,
        "Content-Type": "application/json"
      }
    })).then(async (response) => {
      if (!response) {
        return;
      }
      if (response.status >= 400 && response.status < 600) {
        const { message } = response;
        if (props.action === "apply") {
          let conflicts = [];
          try {
            conflicts = await response.json();
          } catch (e) {
          }
          throw new ApplyError(message, response.status, conflicts);
        }
        throw new Error(message);
      }
      return response.json();
    }).finally(() => {
      setLoadingState(false);
    });
  }
  var ApplyError;
  var init_fetch = __esm({
    "src/Api/fetch.ts"() {
      init_neos_ui_backend_connector();
      ApplyError = class extends Error {
        constructor(message, status, conflicts) {
          super(message);
          this.name = "ApplyError";
          this._status = status;
          this._conflicts = conflicts;
        }
        get status() {
          return this._status;
        }
        get conflicts() {
          return this._conflicts;
        }
      };
    }
  });

  // src/Helpers/format.ts
  function formatRevisionDate(revision) {
    return formatChangeDate(revision.creationDateTime);
  }
  function formatChangeDate(datetime) {
    return new Date(datetime).toLocaleString(void 0, {
      year: "2-digit",
      month: "2-digit",
      day: "2-digit",
      hour: "2-digit",
      minute: "2-digit"
    });
  }
  var init_format = __esm({
    "src/Helpers/format.ts"() {
    }
  });

  // src/Components/RevisionDetails.tsx
  var import_react, import_react_ui_components, RevisionDetails, RevisionDetails_default;
  var init_RevisionDetails = __esm({
    "src/Components/RevisionDetails.tsx"() {
      import_react = __toESM(require_react());
      import_react_ui_components = __toESM(require_react_ui_components());
      init_format();
      RevisionDetails = ({ revision, onUpdate, onClose, translate, isLoading }) => {
        const [label, setLabel] = (0, import_react.useState)(revision.label || "");
        return /* @__PURE__ */ import_react.default.createElement("div", null, /* @__PURE__ */ import_react.default.createElement("table", { style: { width: "100%" } }, /* @__PURE__ */ import_react.default.createElement("tbody", null, /* @__PURE__ */ import_react.default.createElement("tr", null, /* @__PURE__ */ import_react.default.createElement("td", { style: { verticalAlign: "top" } }, /* @__PURE__ */ import_react.default.createElement("strong", null, translate("header.revision"))), /* @__PURE__ */ import_react.default.createElement("td", null, formatRevisionDate(revision))), /* @__PURE__ */ import_react.default.createElement("tr", null, /* @__PURE__ */ import_react.default.createElement("td", { style: { verticalAlign: "top" } }, /* @__PURE__ */ import_react.default.createElement("strong", null, translate("header.creator"))), /* @__PURE__ */ import_react.default.createElement("td", null, revision.creator)), revision.isMoved && /* @__PURE__ */ import_react.default.createElement("tr", null, /* @__PURE__ */ import_react.default.createElement("td", { style: { verticalAlign: "top" } }, /* @__PURE__ */ import_react.default.createElement("strong", null, translate("header.moved"))), /* @__PURE__ */ import_react.default.createElement("td", null, translate("revision.isMoved"))), /* @__PURE__ */ import_react.default.createElement("tr", null, /* @__PURE__ */ import_react.default.createElement("td", { style: { verticalAlign: "top" } }, /* @__PURE__ */ import_react.default.createElement("strong", null, translate("header.identifier"))), /* @__PURE__ */ import_react.default.createElement("td", null, revision.identifier)), /* @__PURE__ */ import_react.default.createElement("tr", null, /* @__PURE__ */ import_react.default.createElement("td", { colSpan: 2 }, /* @__PURE__ */ import_react.default.createElement("strong", null, translate("header.label")))), /* @__PURE__ */ import_react.default.createElement("tr", null, /* @__PURE__ */ import_react.default.createElement("td", { colSpan: 2 }, /* @__PURE__ */ import_react.default.createElement(import_react_ui_components.TextInput, { defaultValue: label, onChange: setLabel }))))), /* @__PURE__ */ import_react.default.createElement("div", { style: { marginTop: "1rem" } }, /* @__PURE__ */ import_react.default.createElement(import_react_ui_components.Button, { style: "warn", onClick: onClose, disabled: isLoading }, translate("action.close")), /* @__PURE__ */ import_react.default.createElement(import_react_ui_components.Button, { style: "success", onClick: () => onUpdate(label), disabled: label == revision.label || isLoading }, translate("action.apply"))));
      };
      RevisionDetails_default = import_react.default.memo(RevisionDetails);
    }
  });

  // src/Components/Diff/AssetPropertyDiff.tsx
  var import_react2, AssetPropertyDiff, AssetPropertyDiff_default;
  var init_AssetPropertyDiff = __esm({
    "src/Components/Diff/AssetPropertyDiff.tsx"() {
      import_react2 = __toESM(require_react());
      AssetPropertyDiff = ({ encodedAssetData }) => {
        let assetData = null;
        if (encodedAssetData) {
          try {
            assetData = JSON.parse(encodedAssetData);
          } catch (e) {
          }
        }
        return assetData?.src ? /* @__PURE__ */ import_react2.default.createElement("a", { href: assetData.src, target: "_blank", title: assetData.alt }, assetData.filename) : /* @__PURE__ */ import_react2.default.createElement("p", null, encodedAssetData);
      };
      AssetPropertyDiff_default = import_react2.default.memo(AssetPropertyDiff);
    }
  });

  // src/Components/Diff/ImagePropertyDiff.tsx
  var import_react3, ImagePropertyDiff, ImagePropertyDiff_default;
  var init_ImagePropertyDiff = __esm({
    "src/Components/Diff/ImagePropertyDiff.tsx"() {
      import_react3 = __toESM(require_react());
      ImagePropertyDiff = ({ encodedImageData }) => {
        let imageData = null;
        if (encodedImageData) {
          try {
            imageData = JSON.parse(encodedImageData);
          } catch (e) {
          }
        }
        return imageData?.src ? /* @__PURE__ */ import_react3.default.createElement("img", { src: imageData.src, alt: imageData.alt, title: imageData.filename }) : /* @__PURE__ */ import_react3.default.createElement("p", null, encodedImageData);
      };
      ImagePropertyDiff_default = import_react3.default.memo(ImagePropertyDiff);
    }
  });

  // src/Components/Diff/DateTimePropertyDiff.tsx
  var import_react4, DateTimePropertyDiff, DateTimePropertyDiff_default;
  var init_DateTimePropertyDiff = __esm({
    "src/Components/Diff/DateTimePropertyDiff.tsx"() {
      import_react4 = __toESM(require_react());
      init_format();
      DateTimePropertyDiff = ({ encodedDateTimeData }) => {
        return /* @__PURE__ */ import_react4.default.createElement("time", null, encodedDateTimeData ? formatChangeDate(encodedDateTimeData) : "-");
      };
      DateTimePropertyDiff_default = import_react4.default.memo(DateTimePropertyDiff);
    }
  });

  // src/Components/Diff/TextPropertyDiff.tsx
  var import_react5, TextPropertyDiff, TextPropertyDiff_default;
  var init_TextPropertyDiff = __esm({
    "src/Components/Diff/TextPropertyDiff.tsx"() {
      import_react5 = __toESM(require_react());
      TextPropertyDiff = ({ text }) => {
        return /* @__PURE__ */ import_react5.default.createElement("span", null, text ? text : "-");
      };
      TextPropertyDiff_default = import_react5.default.memo(TextPropertyDiff);
    }
  });

  // src/Components/Diff/NodePropertyDiff.tsx
  var import_react6, NodePropertyDiff, NodePropertyDiff_default;
  var init_NodePropertyDiff = __esm({
    "src/Components/Diff/NodePropertyDiff.tsx"() {
      import_react6 = __toESM(require_react());
      NodePropertyDiff = ({ value }) => {
        return /* @__PURE__ */ import_react6.default.createElement("span", null, value);
      };
      NodePropertyDiff_default = import_react6.default.memo(NodePropertyDiff);
    }
  });

  // src/Components/Diff/FallbackPropertyDiff.tsx
  var import_react7, FallbackPropertyDiff, FallbackPropertyDiff_default;
  var init_FallbackPropertyDiff = __esm({
    "src/Components/Diff/FallbackPropertyDiff.tsx"() {
      import_react7 = __toESM(require_react());
      FallbackPropertyDiff = ({ value }) => {
        return /* @__PURE__ */ import_react7.default.createElement("div", { style: { overflow: "auto", maxWidth: "calc(100% - 1rem)" } }, /* @__PURE__ */ import_react7.default.createElement("pre", { style: { display: "table", width: "100%", tableLayout: "fixed" } }, value));
      };
      FallbackPropertyDiff_default = import_react7.default.memo(FallbackPropertyDiff);
    }
  });

  // src/Components/Diff/VisualDiff.tsx
  var import_react8, VisualDiff, VisualDiff_default;
  var init_VisualDiff = __esm({
    "src/Components/Diff/VisualDiff.tsx"() {
      import_react8 = __toESM(require_react());
      VisualDiff = ({ diff }) => {
        return Array.isArray(diff) ? /* @__PURE__ */ import_react8.default.createElement(import_react8.default.Fragment, null, diff.map(
          (blocks) => blocks.map((block, index) => /* @__PURE__ */ import_react8.default.createElement("tr", { key: index }, /* @__PURE__ */ import_react8.default.createElement(
            "td",
            {
              dangerouslySetInnerHTML: {
                __html: block.base.lines.join()
              },
              style: { color: "#ff460d", textAlign: "left" }
            }
          ), /* @__PURE__ */ import_react8.default.createElement(
            "td",
            {
              dangerouslySetInnerHTML: {
                __html: block.changed.lines.join()
              },
              style: { color: "#00a338", textAlign: "left" }
            }
          )))
        )) : /* @__PURE__ */ import_react8.default.createElement("p", null, "Cannot render visual diff");
      };
      VisualDiff_default = import_react8.default.memo(VisualDiff);
    }
  });

  // src/Components/ContentChangeDiff.tsx
  var import_react9, import_react_ui_components2, ContentChangeDiff, ContentChangeDiff_default;
  var init_ContentChangeDiff = __esm({
    "src/Components/ContentChangeDiff.tsx"() {
      import_react9 = __toESM(require_react());
      import_react_ui_components2 = __toESM(require_react_ui_components());
      init_format();
      init_AssetPropertyDiff();
      init_ImagePropertyDiff();
      init_DateTimePropertyDiff();
      init_TextPropertyDiff();
      init_NodePropertyDiff();
      init_FallbackPropertyDiff();
      init_VisualDiff();
      ContentChangeDiff = ({ nodeChanges, contentDimensions, translate }) => {
        const { node, type, changes = [] } = nodeChanges;
        const changeColor = (0, import_react9.useMemo)(() => {
          return type === "changeNode" ? "#ff8700" : type === "addNode" ? "#00a338" : "#ff460d";
        }, [type]);
        const dimensionLabel = (0, import_react9.useMemo)(() => {
          return /* @__PURE__ */ import_react9.default.createElement(import_react9.default.Fragment, null, Object.keys(node.dimensions).map((dimensionName) => {
            const contentDimension = contentDimensions[dimensionName];
            if (!contentDimension) {
              return null;
            }
            const presetKey = node.dimensions[dimensionName][0];
            return presetKey ? /* @__PURE__ */ import_react9.default.createElement("span", { key: dimensionName, title: `${translate(contentDimension.label)}: ${presetKey}` }, contentDimension.icon && /* @__PURE__ */ import_react9.default.createElement(import_react_ui_components2.Icon, { icon: contentDimension.icon, style: { marginRight: "0.5em", color: "#222" } }), translate(contentDimension.presets[presetKey].label)) : null;
          }));
        }, [node.dimensions]);
        const renderChange = (0, import_react9.useCallback)((text, type2) => {
          switch (type2) {
            case "text":
              return /* @__PURE__ */ import_react9.default.createElement(TextPropertyDiff_default, { text });
            case "image":
              return /* @__PURE__ */ import_react9.default.createElement(ImagePropertyDiff_default, { encodedImageData: text });
            case "asset":
              return /* @__PURE__ */ import_react9.default.createElement(AssetPropertyDiff_default, { encodedAssetData: text });
            case "datetime":
              return /* @__PURE__ */ import_react9.default.createElement(DateTimePropertyDiff_default, { encodedDateTimeData: text });
            case "array":
              try {
                const changes2 = JSON.parse(text);
                return /* @__PURE__ */ import_react9.default.createElement("ul", null, changes2.map((change, index) => {
                  return /* @__PURE__ */ import_react9.default.createElement("li", { key: index }, /* @__PURE__ */ import_react9.default.createElement(FallbackPropertyDiff_default, { value: change }));
                }));
              } catch (e) {
                return /* @__PURE__ */ import_react9.default.createElement(FallbackPropertyDiff_default, { value: text });
              }
            case "node":
              return /* @__PURE__ */ import_react9.default.createElement(NodePropertyDiff_default, { value: text });
          }
          return /* @__PURE__ */ import_react9.default.createElement(FallbackPropertyDiff_default, { value: text });
        }, []);
        return /* @__PURE__ */ import_react9.default.createElement(
          "div",
          {
            style: {
              background: "#eee",
              color: "#252525",
              marginBottom: "1rem",
              padding: "1rem 1rem",
              borderLeft: `3px solid ${changeColor}`
            }
          },
          /* @__PURE__ */ import_react9.default.createElement("div", { style: { display: "flex", justifyContent: "space-between" } }, /* @__PURE__ */ import_react9.default.createElement("span", null, /* @__PURE__ */ import_react9.default.createElement("strong", null, translate("diff." + type)), node.nodeType.icon && /* @__PURE__ */ import_react9.default.createElement(
            import_react_ui_components2.Icon,
            {
              icon: node.nodeType.icon,
              title: node.nodeType.label ? translate(node.nodeType.label) : node.nodeType.name,
              style: { margin: "0 0.5em", color: "#222" },
              color: "primaryBlue"
            }
          ), node.label), /* @__PURE__ */ import_react9.default.createElement("span", null, dimensionLabel, /* @__PURE__ */ import_react9.default.createElement(import_react_ui_components2.Icon, { icon: "clock", style: { margin: "0 0.5em", color: "#222" } }), formatChangeDate(node.lastModificationDateTime))),
          Object.keys(changes).map((propertyName) => {
            const change = changes[propertyName];
            return /* @__PURE__ */ import_react9.default.createElement(
              "div",
              {
                key: propertyName,
                style: { borderTop: "1px solid #323232", marginTop: "1rem", padding: "1rem" }
              },
              /* @__PURE__ */ import_react9.default.createElement("div", null, translate("diff.propertyLabel", change.propertyLabel, {
                propertyLabel: change.propertyLabel
              })),
              /* @__PURE__ */ import_react9.default.createElement("table", { style: { width: "100%", borderSpacing: 0 } }, /* @__PURE__ */ import_react9.default.createElement("thead", null, /* @__PURE__ */ import_react9.default.createElement("tr", null, /* @__PURE__ */ import_react9.default.createElement("th", { style: { textAlign: "left", width: "50%" } }, translate("diff.old")), /* @__PURE__ */ import_react9.default.createElement("th", { style: { textAlign: "left", width: "50%" } }, translate("diff.new")))), /* @__PURE__ */ import_react9.default.createElement("tbody", null, Array.isArray(change.diff) && change.diff.length > 0 ? /* @__PURE__ */ import_react9.default.createElement(VisualDiff_default, { diff: change.diff }) : /* @__PURE__ */ import_react9.default.createElement("tr", null, /* @__PURE__ */ import_react9.default.createElement("td", { style: { color: "#ff460d" } }, renderChange(change.original, change.originalType)), /* @__PURE__ */ import_react9.default.createElement("td", { style: { color: "#00a338" } }, renderChange(change.changed, change.changedType)))))
            );
          })
        );
      };
      ContentChangeDiff_default = import_react9.default.memo(ContentChangeDiff);
    }
  });

  // src/Components/ErrorBoundary.tsx
  var import_react10, ErrorBoundary;
  var init_ErrorBoundary = __esm({
    "src/Components/ErrorBoundary.tsx"() {
      import_react10 = __toESM(require_react());
      ErrorBoundary = class extends import_react10.default.Component {
        constructor(props) {
          super(props);
          this.state = { hasError: false };
        }
        static getDerivedStateFromError() {
          return { hasError: true };
        }
        componentDidCatch(error, errorInfo) {
          console.error(error, errorInfo);
        }
        render() {
          if (this.state.hasError) {
            return /* @__PURE__ */ import_react10.default.createElement("strong", { style: { color: "red" } }, this.props.text || "Something went wrong.");
          }
          return this.props.children;
        }
      };
    }
  });

  // src/Components/RevisionDiff.tsx
  var import_react11, import_react_ui_components3, RevisionDiff, RevisionDiff_default;
  var init_RevisionDiff = __esm({
    "src/Components/RevisionDiff.tsx"() {
      import_react11 = __toESM(require_react());
      import_react_ui_components3 = __toESM(require_react_ui_components());
      init_fetch();
      init_ContentChangeDiff();
      init_ErrorBoundary();
      init_format();
      RevisionDiff = ({
        documentNode,
        revision,
        translate,
        applyRevision,
        onClose,
        contentDimensions
      }) => {
        const [isLoading, setIsLoading] = (0, import_react11.useState)(true);
        const [changes, setChanges] = (0, import_react11.useState)({});
        const [message, setMessage] = (0, import_react11.useState)("");
        const version = (0, import_react11.useMemo)(
          () => revision ? revision.label || translate("revision.label", `By ${revision.creator}`, {
            creator: revision.creator
          }) : "",
          [revision]
        );
        const fetchChanges = (0, import_react11.useCallback)(() => {
          setIsLoading(true);
          fetchFromBackend(
            { action: "getDiff", params: { node: documentNode, revision } },
            setIsLoading
          ).then(({ diff }) => setChanges(diff)).catch((error) => {
            setMessage(translate("error.failedFetchingChanges"));
            console.error(error.message);
          });
        }, [revision]);
        (0, import_react11.useEffect)(() => {
          fetchChanges();
        }, [revision]);
        return /* @__PURE__ */ import_react11.default.createElement("div", { style: { padding: "1rem", height: "100%", display: "flex", flexDirection: "column", position: "absolute", inset: "0" } }, /* @__PURE__ */ import_react11.default.createElement("h1", { style: { marginBottom: "2rem", fontSize: "1.5em", lineHeight: 1.3 } }, translate("diff.header", "The revision contains the following changes", { version, date: formatRevisionDate(revision) })), revision.isMoved && /* @__PURE__ */ import_react11.default.createElement("div", { style: { marginBottom: "2rem", fontWeight: "bold" } }, /* @__PURE__ */ import_react11.default.createElement(import_react_ui_components3.Icon, { icon: "exclamation-triangle", style: { marginRight: "0.5rem", color: "#ff8700" } }), translate("diff.moved", "The document was moved to another location.")), /* @__PURE__ */ import_react11.default.createElement("div", { style: { overflow: "auto" } }, isLoading ? /* @__PURE__ */ import_react11.default.createElement("div", null, /* @__PURE__ */ import_react11.default.createElement(import_react_ui_components3.Icon, { icon: "spinner", spin: true, color: "primaryBlue" }), " Loading \u2026") : changes && Object.keys(changes).length > 0 ? Object.keys(changes).map(
          (nodeIdentifier) => Object.keys(changes[nodeIdentifier]).map((dimensionHash) => /* @__PURE__ */ import_react11.default.createElement("div", { key: nodeIdentifier, style: { marginBottom: "1rem" } }, /* @__PURE__ */ import_react11.default.createElement(
            ErrorBoundary,
            {
              text: `Diff for node ${changes[nodeIdentifier][dimensionHash].node?.label || nodeIdentifier} could not be rendered. Please check the logs.`
            },
            /* @__PURE__ */ import_react11.default.createElement(
              ContentChangeDiff_default,
              {
                nodeChanges: changes[nodeIdentifier][dimensionHash],
                translate,
                contentDimensions
              }
            )
          )))
        ) : /* @__PURE__ */ import_react11.default.createElement("p", null, message ? message : translate("diff.empty", "No changes have been found"))), /* @__PURE__ */ import_react11.default.createElement("div", { style: { marginTop: "2rem", display: "flex", justifyContent: "space-between" } }, /* @__PURE__ */ import_react11.default.createElement(import_react_ui_components3.Button, { style: "warn", onClick: onClose }, translate("action.close")), /* @__PURE__ */ import_react11.default.createElement(
          import_react_ui_components3.Button,
          {
            style: "success",
            onClick: () => applyRevision(revision),
            disabled: isLoading || Object.keys(changes).length === 0
          },
          /* @__PURE__ */ import_react11.default.createElement(import_react_ui_components3.Icon, { icon: "check" }),
          " ",
          translate("action.apply")
        )));
      };
      RevisionDiff_default = import_react11.default.memo(RevisionDiff);
    }
  });

  // src/Components/RevisionListItem.tsx
  var import_react12, import_react_ui_components4, RevisionListItem, RevisionListItem_default;
  var init_RevisionListItem = __esm({
    "src/Components/RevisionListItem.tsx"() {
      import_react12 = __toESM(require_react());
      import_react_ui_components4 = __toESM(require_react_ui_components());
      init_format();
      RevisionListItem = ({
        revision,
        translate,
        showRevision,
        setSelectedRevision,
        deleteRevision,
        showDeleteButton,
        allowApply
      }) => {
        return /* @__PURE__ */ import_react12.default.createElement("tr", { key: revision.creationDateTime }, /* @__PURE__ */ import_react12.default.createElement(
          "td",
          {
            title: translate("tooltip.revisionLabel", "Created on {revisionDate} by {creator}", {
              revisionDate: formatRevisionDate(revision),
              creator: revision.creator
            })
          },
          /* @__PURE__ */ import_react12.default.createElement("div", null, revision.label || translate("revision.label", "By {creator}", {
            creator: revision.creator
          })),
          /* @__PURE__ */ import_react12.default.createElement("time", { style: { opacity: 0.5 } }, formatRevisionDate(revision))
        ), /* @__PURE__ */ import_react12.default.createElement("td", { style: { textAlign: "center", verticalAlign: "middle" } }, /* @__PURE__ */ import_react12.default.createElement(
          import_react_ui_components4.IconButton,
          {
            onClick: () => showRevision(revision),
            icon: "trash-restore",
            style: "primary",
            hoverStyle: "success",
            size: "small",
            disabled: !allowApply,
            title: allowApply ? translate("action.apply.title", "Apply revision {revisionDate} by {creator}", {
              revisionDate: formatRevisionDate(revision),
              creator: revision.creator
            }) : translate("action.apply.disabled.title", "This revision cannot be applied")
          }
        ), /* @__PURE__ */ import_react12.default.createElement(
          import_react_ui_components4.IconButton,
          {
            onClick: () => setSelectedRevision(revision),
            icon: "comment",
            style: "primary",
            hoverStyle: "warn",
            size: "small",
            title: translate("action.edit.title", "Edit revision {revisionDate} by {creator}", {
              revisionDate: formatRevisionDate(revision),
              creator: revision.creator
            })
          }
        ), showDeleteButton && /* @__PURE__ */ import_react12.default.createElement(
          import_react_ui_components4.IconButton,
          {
            onClick: () => deleteRevision(revision),
            icon: "times-circle",
            style: "primary",
            hoverStyle: "error",
            size: "small",
            title: translate("action.delete.title", "Delete revision {revisionDate} by {creator}", {
              revisionDate: formatRevisionDate(revision),
              creator: revision.creator
            })
          }
        )));
      };
      RevisionListItem_default = import_react12.default.memo(RevisionListItem);
    }
  });

  // src/Components/RevisionList.tsx
  var import_react13, import_react_ui_components5, RevisionList, RevisionList_default;
  var init_RevisionList = __esm({
    "src/Components/RevisionList.tsx"() {
      import_react13 = __toESM(require_react());
      import_react_ui_components5 = __toESM(require_react_ui_components());
      init_fetch();
      init_format();
      init_RevisionDetails();
      init_RevisionDiff();
      init_RevisionListItem();
      RevisionList = ({
        documentNode,
        addFlashMessage,
        reloadDocument,
        i18nRegistry,
        frontendConfiguration,
        renderSecondaryInspector,
        contentDimensions
      }) => {
        const [revisions, setRevisions] = (0, import_react13.useState)([]);
        const [message, setMessage] = (0, import_react13.useState)("");
        const [selectedRevision, setSelectedRevision] = (0, import_react13.useState)(null);
        const [isLoading, setIsLoading] = (0, import_react13.useState)(true);
        const translate = (0, import_react13.useCallback)(
          (id, fallback = "", params = [], packageKey = "NEOSidekick.Revisions", sourceName = "Main") => {
            return i18nRegistry.translate(id, fallback, params, packageKey, sourceName);
          },
          []
        );
        const fetchRevisions = (0, import_react13.useCallback)(() => {
          setIsLoading(true);
          fetchFromBackend({ action: "get", params: { node: documentNode } }, setIsLoading).then(({ revisions: revisions2 }) => setRevisions(revisions2)).catch((error) => {
            setMessage(translate("error.failedFetchingRevisions"));
            console.error(error.message);
          });
        }, [documentNode]);
        const resolveConflicts = (0, import_react13.useCallback)((revision, conflicts) => {
          if (confirm(
            translate(
              "error.verifyResolveConflicts",
              "Some conflicts were detected. Do you still want to apply the revision?{conflicts}",
              { conflicts: "\n\n" + conflicts.join("\n\n") }
            )
          )) {
            applyRevision(revision, true);
          }
        }, []);
        const applyRevision = (0, import_react13.useCallback)((revision, force = false) => {
          fetchFromBackend(
            {
              action: "apply",
              params: { node: documentNode, revision, force }
            },
            setIsLoading
          ).then(() => {
            addFlashMessage(
              translate("success.revisionApplied"),
              translate("success.revisionApplied.message", 'Revision "{label}" by "{creator}" applied.', {
                label: revision.label || formatRevisionDate(revision),
                creator: revision.creator
              }),
              "success"
            );
            reloadDocument();
            setMessage("");
          }).catch((error) => {
            const { status, conflicts } = error;
            if (status === 409) {
              resolveConflicts(revision, conflicts);
            } else {
              setMessage(translate("error.failedApplyingRevision"));
              console.error(error);
            }
          });
        }, []);
        const deleteRevision = (0, import_react13.useCallback)((revision) => {
          if (confirm(
            translate(
              "confirm.deleteRevision",
              "Do you really want to delete revision {revisionDate} by {creator}?",
              {
                revisionDate: formatRevisionDate(revision),
                creator: revision.creator
              }
            )
          )) {
            fetchFromBackend({ action: "delete", params: { revision } }, setIsLoading).then(() => {
              addFlashMessage(
                translate("success.revisionDeleted"),
                translate("success.revisionDeleted.message", 'Revision "{label}" by "{creator} deleted.', {
                  label: revision.label || formatRevisionDate(revision),
                  creator: revision.creator
                }),
                "success"
              );
              setMessage("");
              fetchRevisions();
            }).catch((error) => {
              setMessage(translate("error.failedDeletingRevision"));
              console.error(error.message);
            });
          }
        }, []);
        const updateSelectedRevision = (0, import_react13.useCallback)(
          (label) => {
            fetchFromBackend({ action: "setlabel", params: { revision: selectedRevision, label } }, setIsLoading).then(() => {
              addFlashMessage(
                translate("success.revisionUpdated"),
                translate("success.revisionUpdated.message", 'Revision {label} by "{creator}" updated.', {
                  label: selectedRevision.label || formatRevisionDate(selectedRevision),
                  creator: selectedRevision.creator
                }),
                "success"
              );
              setSelectedRevision(null);
              fetchRevisions();
            }).catch((error) => {
              setMessage(translate("error.failedUpdatingRevision"));
              console.error(error.message);
            });
          },
          [selectedRevision]
        );
        const showRevision = (0, import_react13.useCallback)((revision) => {
          if (!revision) {
            renderSecondaryInspector(null, null);
          } else {
            renderSecondaryInspector("REVISIONS_COMPARE", () => /* @__PURE__ */ import_react13.default.createElement(
              RevisionDiff_default,
              {
                documentNode,
                translate,
                revision,
                onClose: showRevision,
                applyRevision,
                contentDimensions
              }
            ));
          }
        }, []);
        (0, import_react13.useEffect)(fetchRevisions, [documentNode]);
        return /* @__PURE__ */ import_react13.default.createElement("div", null, message && /* @__PURE__ */ import_react13.default.createElement("div", { style: { color: "red", margin: "1rem 0" }, role: "alert" }, message), isLoading && /* @__PURE__ */ import_react13.default.createElement("div", null, /* @__PURE__ */ import_react13.default.createElement(import_react_ui_components5.Icon, { icon: "spinner", spin: true, color: "primaryBlue" }), " Loading \u2026"), selectedRevision ? /* @__PURE__ */ import_react13.default.createElement(
          RevisionDetails_default,
          {
            revision: selectedRevision,
            onUpdate: updateSelectedRevision,
            onClose: () => setSelectedRevision(null),
            translate,
            isLoading
          }
        ) : revisions.length ? /* @__PURE__ */ import_react13.default.createElement("table", { style: { width: "100%", maxWidth: "100%" } }, /* @__PURE__ */ import_react13.default.createElement("thead", null, /* @__PURE__ */ import_react13.default.createElement("tr", null, /* @__PURE__ */ import_react13.default.createElement("th", { style: { textAlign: "left" } }, translate("header.label")), /* @__PURE__ */ import_react13.default.createElement("th", { style: { width: "100px" } }, translate("header.actions")))), /* @__PURE__ */ import_react13.default.createElement("tbody", null, revisions.map((revision, index) => /* @__PURE__ */ import_react13.default.createElement(import_react13.default.Fragment, { key: index }, /* @__PURE__ */ import_react13.default.createElement(
          RevisionListItem_default,
          {
            allowApply: index > 0 && !revision.isEmpty,
            revision,
            translate,
            showDeleteButton: frontendConfiguration.showDeleteButton,
            deleteRevision,
            setSelectedRevision,
            showRevision
          }
        ), index < revisions.length - 1 && /* @__PURE__ */ import_react13.default.createElement("tr", null, /* @__PURE__ */ import_react13.default.createElement("td", { colSpan: 2, style: { borderBottom: "1px solid #3f3f3f" } })))))) : !isLoading ? /* @__PURE__ */ import_react13.default.createElement("em", null, translate("list.noRevisionsFound")) : "");
      };
      RevisionList_default = import_react13.default.memo(RevisionList);
    }
  });

  // src/RevisionsView.tsx
  var import_react14, import_prop_types, import_react_redux, import_plow_js, import_neos_ui_redux_store, import_neos_ui_decorators, RevisionsView;
  var init_RevisionsView = __esm({
    "src/RevisionsView.tsx"() {
      import_react14 = __toESM(require_react());
      import_prop_types = __toESM(require_prop_types());
      import_react_redux = __toESM(require_react_redux());
      import_plow_js = __toESM(require_plow_js());
      import_neos_ui_redux_store = __toESM(require_neos_ui_redux_store());
      import_neos_ui_decorators = __toESM(require_neos_ui_decorators());
      init_RevisionList();
      RevisionsView = class extends import_react14.PureComponent {
        constructor(props) {
          super(props);
        }
        render() {
          const documentNode = this.props.getNodeByContextPath(this.props.documentNodePath);
          const {
            addFlashMessage,
            reloadDocument,
            i18nRegistry,
            frontendConfiguration,
            renderSecondaryInspector,
            contentDimensions
          } = this.props;
          return /* @__PURE__ */ import_react14.default.createElement(
            RevisionList_default,
            {
              documentNode,
              addFlashMessage,
              reloadDocument,
              i18nRegistry,
              frontendConfiguration,
              renderSecondaryInspector,
              contentDimensions
            }
          );
        }
      };
      RevisionsView.propTypes = {
        documentNodePath: import_prop_types.default.string.isRequired,
        i18nRegistry: import_prop_types.default.object.isRequired,
        getNodeByContextPath: import_prop_types.default.func.isRequired,
        addFlashMessage: import_prop_types.default.func.isRequired,
        reloadDocument: import_prop_types.default.func.isRequired,
        frontendConfiguration: import_prop_types.default.object.isRequired,
        renderSecondaryInspector: import_prop_types.default.func.isRequired
      };
      RevisionsView = __decorateClass([
        (0, import_react_redux.connect)((state) => ({
          getNodeByContextPath: import_neos_ui_redux_store.selectors.CR.Nodes.nodeByContextPath(state)
        })),
        (0, import_react_redux.connect)(
          (0, import_plow_js.$transform)({
            documentNodePath: (0, import_plow_js.$get)("cr.nodes.documentNode"),
            contentDimensions: import_neos_ui_redux_store.selectors.CR.ContentDimensions.byName,
            allowedPresets: import_neos_ui_redux_store.selectors.CR.ContentDimensions.allowedPresets,
            activePresets: import_neos_ui_redux_store.selectors.CR.ContentDimensions.activePresets
          }),
          {
            addFlashMessage: import_neos_ui_redux_store.actions.UI.FlashMessages.add,
            reloadDocument: import_neos_ui_redux_store.actions.UI.ContentCanvas.reload
          }
        ),
        (0, import_neos_ui_decorators.neos)((globalRegistry) => ({
          i18nRegistry: globalRegistry.get("i18n"),
          frontendConfiguration: globalRegistry.get("frontendConfiguration").get("NEOSidekick.Revisions")
        }))
      ], RevisionsView);
    }
  });

  // src/manifest.js
  var manifest_exports = {};
  var init_manifest2 = __esm({
    "src/manifest.js"() {
      init_dist();
      init_RevisionsView();
      dist_default("CodeQ:Revisions", {}, (globalRegistry) => {
        const viewsRegistry = globalRegistry.get("inspector").get("views");
        viewsRegistry.set("NEOSidekick.Revisions/Inspector/Views/RevisionsView", {
          component: RevisionsView
        });
      });
    }
  });

  // src/index.js
  init_manifest2();
})();
