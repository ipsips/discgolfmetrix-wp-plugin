!function(t){function e(i){if(r[i])return r[i].exports;var s=r[i]={exports:{},id:i,loaded:!1};return t[i].call(s.exports,s,s.exports,e),s.loaded=!0,s.exports}var r={};return e.m=t,e.c=r,e.p="",e(0)}([function(t,e){"use strict";function r(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}var i=jQuery,s=function t(){var e=this;r(this,t),this.onFiltersUpdate=function(t,r){var s=e.$filtersSelected.children().map(function(t,e){return i(e).data("name")}).toArray();console.log(s),e.$filtersSelectedInput.val(JSON.stringify(s))},this.$filtersSelected=i(".filters-selected"),this.$filtersSelectedInput=i('input[name="discgolfmetrix_options[results_filter]"]'),i(".discgolfmetrix-results-filter").sortable({connectWith:".discgolfmetrix-results-filter"}),this.$filtersSelected.on("sortupdate",this.onFiltersUpdate)};new s}]);
//# sourceMappingURL=discgolfmetrix-settings.js.map