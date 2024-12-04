import * as __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__ from "@wordpress/interactivity";
/******/ var __webpack_modules__ = ({

/***/ "@wordpress/interactivity-router":
/*!**************************************************!*\
  !*** external "@wordpress/interactivity-router" ***!
  \**************************************************/
/***/ ((module) => {

module.exports = import("@wordpress/interactivity-router");;

/***/ }),

/***/ "@wordpress/interactivity":
/*!*******************************************!*\
  !*** external "@wordpress/interactivity" ***!
  \*******************************************/
/***/ ((module) => {

module.exports = __WEBPACK_EXTERNAL_MODULE__wordpress_interactivity_8e89b257__;

/***/ })

/******/ });
/************************************************************************/
/******/ // The module cache
/******/ var __webpack_module_cache__ = {};
/******/ 
/******/ // The require function
/******/ function __webpack_require__(moduleId) {
/******/ 	// Check if module is in cache
/******/ 	var cachedModule = __webpack_module_cache__[moduleId];
/******/ 	if (cachedModule !== undefined) {
/******/ 		return cachedModule.exports;
/******/ 	}
/******/ 	// Create a new module (and put it into the cache)
/******/ 	var module = __webpack_module_cache__[moduleId] = {
/******/ 		// no module.id needed
/******/ 		// no module.loaded needed
/******/ 		exports: {}
/******/ 	};
/******/ 
/******/ 	// Execute the module function
/******/ 	__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 
/******/ 	// Return the exports of the module
/******/ 	return module.exports;
/******/ }
/******/ 
/************************************************************************/
/******/ /* webpack/runtime/make namespace object */
/******/ (() => {
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = (exports) => {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/ })();
/******/ 
/************************************************************************/
var __webpack_exports__ = {};
/*!*********************************!*\
  !*** ./src/data-tables/view.js ***!
  \*********************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/interactivity */ "@wordpress/interactivity");
/**
 * WordPress dependencies
 */

const {
  state,
  callbacks,
  actions
} = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.store)('data-tables', {
  state: {
    get isYearChecked() {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      return context.donationYear === state.donationYear;
    },
    get isTypeChecked() {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      console.log();
      return context.donorType === state.donorType;
    },
    get isChecked() {
      return (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)().year === state.donationYear;
    },
    get translatedType() {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      return state.translated[context.item];
    }
  },
  actions: {
    *getData() {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      const url = new URL(`https://${window.location.hostname}/wp-json/site-functionality/v1/transactions/`);
      const params = new URLSearchParams(state.query).toString();
      const data = yield fetch(`${url}?${params}`).then(function (response) {
        return response.json();
      });
      context.dataTable = JSON.stringify(data);
      console.log(`${url}?${params}`, context.dataTable);
    },
    *updateSearch() {
      const {
        ref
      } = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getElement)();
      const {
        value
      } = ref;

      // Don't navigate if the search didn't really change.
      if (value === state.searchValue) return;
      state.searchValue = value;
      if (value === '') {
        // If the search is empty, navigate to the home page.
        const {
          actions
        } = yield Promise.resolve(/*! import() */).then(__webpack_require__.bind(__webpack_require__, /*! @wordpress/interactivity-router */ "@wordpress/interactivity-router"));
        yield actions.navigate('/');
      } else {
        // If not, navigate to the new URL.
        yield updateURL(value);
      }
    },
    isChecked: () => {
      const element = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getElement)();
      console.log(element.attributes.value === state[element.attributes['data-key']]);
      return element.attributes.value === state[element.attributes['data-key']];
    },
    setValue: event => {
      event.preventDefault();
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      const key = event.target.getAttribute('data-key');
      const value = event.target.getAttribute('value');
      context[key] = value;
      state[key] = value;
      actions.isChecked();
      actions.setQuery();
    },
    setQuery: () => {
      let query = {};
      if (state.donationYear) {
        query['years'] = 'all' == state.donationYear ? '' : state.donationYear;
      }
      if (state.donorType) {
        query['donor-types'] = 'all' == state.donorType ? '' : state.donorType;
      }
      if (state.thinkTank) {
        query['think-tanks'] = state.thinkTank;
      }
      if (state.donor) {
        query['donors'] = state.donor;
      }
      state.query = query;
    },
    getTerm: async (taxonomy, slug) => {
      const url = new URL(`https://${window.location.hostname}/wp-json/wp/v2/${taxonomy}/?slug=${slug}`);
      const response = await fetch(url);
      const data = await response.json();
      return data;
    },
    getTermId: async (taxonomy, slug) => {
      const data = await actions.getTerm(taxonomy, slug);
      return data[0]?.id;
    },
    handleYear: () => {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      state.donationYear = context.year;
      actions.setQuery();
      actions.getData();
    },
    handleType: event => {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      const value = event.target.getAttribute('value');
      // context.donorType = value;
      state.donorType = value;
      actions.setQuery();
      actions.getData();
    }
  },
  callbacks: {
    log: () => {
      const context = (0,_wordpress_interactivity__WEBPACK_IMPORTED_MODULE_0__.getContext)();
      // console.log( 'log.context', JSON.stringify( context ) );
      // console.log( 'log.state', JSON.stringify( state.query ) );
    }
  }
});

//# sourceMappingURL=view.js.map