'use strict';

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _toConsumableArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

/**
 * Check the browsers console capabilities and bundle them into general functions
 * If the build environment was "production" only put out error messages.
 */

var ConsoleShim = function () {
    function ConsoleShim() {
        var param = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
        var silent = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

        _classCallCheck(this, ConsoleShim);

        this.param = param;
        this.silent = silent;
        this.collector = [];
        this.currentGroupDescription = '';
        this.activeGroups = 0;
        this.timeHolder = null;
        this.methods = ['group', 'groupEnd', 'log', 'trace', 'time', 'timeEnd', 'error', 'warn'];
    }

    _createClass(ConsoleShim, [{
        key: '_generateError',
        value: function _generateError() {
            try {
                throw new Error();
            } catch (err) {
                return err;
            }
        }
    }, {
        key: '_insertParamToArguments',
        value: function _insertParamToArguments() {
            if (this.param !== '') {
                var args = Array.from(arguments);
                args.unshift(this.param);
                return args;
            }
            return Array.from(arguments);
        }
    }, {
        key: 'setSilent',
        value: function setSilent() {
            var newValue = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;

            this.silent = newValue || !this.silent;
        }
        //Start grouping logs

    }, {
        key: 'group',
        value: function group() {
            if (this.silent) {
                return;
            }
            var args = this._insertParamToArguments(arguments);
            if (typeof console.group === 'function') {
                console.group.apply(console, args);
                return;
            }
            var description = args[0] || 'GROUP';
            this.currentGroupDescription = description;
            this.activeGroups++;
        }
        //Stop grouping logs

    }, {
        key: 'groupEnd',
        value: function groupEnd() {
            if (this.silent) {
                return;
            }
            var args = this._insertParamToArguments(arguments);
            if (typeof console.groupEnd === 'function') {
                console.groupEnd.apply(console, args);
                return;
            }
            this.currentGroupDescription = '';
            this.activeGroups--;
            this.activeGroups = this.activeGroups === 0 ? 0 : this.activeGroups--;
        }
        //Simplest mechanism to log stuff
        // Aware of the group shim

    }, {
        key: 'log',
        value: function log() {
            if (this.silent) {
                return;
            }
            var args = this._insertParamToArguments(arguments);
            if (typeof console.group === 'function') {
                var _console$log;

                (_console$log = console.log).call.apply(_console$log, [console].concat(_toConsumableArray(args)));
                return;
            }
            args.shift();
            args.unshift(' '.repeat(this.activeGroups * 2));
            this.log.apply(this, args);
        }
        //Trace back the apply.
        //Uses either the inbuilt function console trace or opens a shim to trace by calling this._insertParamToArguments(arguments).callee

    }, {
        key: 'trace',
        value: function trace() {
            if (this.silent) {
                return;
            }
            var args = this._insertParamToArguments(arguments);
            if (typeof console.trace === 'function') {
                console.trace.apply(console, args);
                return;
            }
            var artificialError = this._generateError();
            if (artificialError.stack) {
                this.log.apply(console, artificialError.stack);
                return;
            }

            this.log(args);
            if (arguments.callee != undefined) {
                this.trace.apply(console, arguments.callee);
            }
        }
    }, {
        key: 'time',
        value: function time() {
            if (this.silent) {
                return;
            }
            var args = this._insertParamToArguments(arguments);
            if (typeof console.time === 'function') {
                console.time.apply(console, args);
                return;
            }

            this.timeHolder = new Date();
        }
    }, {
        key: 'timeEnd',
        value: function timeEnd() {
            if (this.silent) {
                return;
            }
            var args = this._insertParamToArguments(arguments);
            if (typeof console.timeEnd === 'function') {
                console.timeEnd.apply(console, args);
                return;
            }
            var diff = new Date() - this.timeHolder;
            this.log('Took ' + Math.floor(diff / (1000 * 60 * 60)) + ' hours, ' + Math.floor(diff / (1000 * 60)) + ' minutes and ' + Math.floor(diff / 1000) + ' seconds ( ' + diff + ' ms)');
            this.time = new Date();
        }
    }, {
        key: 'error',
        value: function error() {
            var args = this._insertParamToArguments(arguments);
            if (typeof console.error === 'function') {
                console.error.apply(args);
                return;
            }

            this.log('--- ERROR ---');
            this.log(args);
        }
    }, {
        key: 'err',
        value: function err() {
            this.error.apply(this, arguments);
        }
    }, {
        key: 'debug',
        value: function debug() {
            this.trace.apply(this, arguments);
        }
    }, {
        key: 'warn',
        value: function warn() {
            var args = this._insertParamToArguments(arguments);
            if (typeof console.warn === 'function') {
                console.warn.apply(args);
                return;
            }

            this.log('--- WARN ---');
            this.log(args);
        }
    }]);

    return ConsoleShim;
}();

if (window.debugState.backend || window.debugState.frontend) {
    var globalLSConsole = new ConsoleShim('LSLOG');
    window.console.ls = globalLSConsole;
} else {
    var globalLSConsole = new ConsoleShim('LSLOG', true);
    window.console.ls = globalLSConsole;
}