/**
 * Check the browsers console capabilities and bundle them into general functions
 * If the build environment was "production" only put out error messages.
 */


class ConsoleShim {
    constructor(param='', silent=false) {

        this.param = param;
        this.silent = silent;
        this.collector = [];
        this.currentGroupDescription = '';
        this.activeGroups = 0;
        this.timeHolder = null;
        this.methods = [
            'group', 'groupEnd', 'log', 'trace', 'time', 'timeEnd', 'error', 'warn'
        ];
    }

    _generateError() {
        try {
            throw new Error();
        } catch (err) {
            return err;
        }
    }
    _insertParamToArguments(){
        if(this.param !== ''){
            let args = Array.from(arguments);
            args.unshift(this.param);
            return args;
        }
        return Array.from(arguments);
    }
    //Start grouping logs
    group() {
        if(this.silent) { return; }
        const args = this._insertParamToArguments(arguments);
        if (typeof console.group === 'function') {
            console.group.apply(console, args);
            return;
        }
        const description = args[0] || 'GROUP';
        this.currentGroupDescription = description;
        this.activeGroups++;
    }
    //Stop grouping logs
    groupEnd() {
        if(this.silent) { return; }
        const args = this._insertParamToArguments(arguments);
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
    log() {
        if(this.silent) { return; }
        const args = this._insertParamToArguments(arguments);
        if (typeof console.group === 'function') {
            console.log.call(console, ...args);
            return;
        }
        args.shift();
        args.unshift(' '.repeat(this.activeGroups * 2));
        this.log.apply(this,args);
    }
    //Trace back the apply.
    //Uses either the inbuilt function console trace or opens a shim to trace by calling this._insertParamToArguments(arguments).callee
    trace() {
        if(this.silent) { return; }
        const args = this._insertParamToArguments(arguments);        
        if (typeof console.trace === 'function') {
            console.trace.apply(console, args);
            return;
        }
        const artificialError = this._generateError();
        if (artificialError.stack) {
            this.log.apply(console, artificialError.stack);
            return;
        }

        this.log(args);
        if (arguments.callee != undefined) {
            this.trace.apply(console, arguments.callee);
        }
    }

    time() {
        if(this.silent) { return; }
        const args = this._insertParamToArguments(arguments);    
        if (typeof console.time === 'function') {
            console.time.apply(console, args);
            return;
        }

        this.timeHolder = new Date();
    }

    timeEnd() {
        if(this.silent) { return; }
        const args = this._insertParamToArguments(arguments);
        if (typeof console.timeEnd === 'function') {
            console.timeEnd.apply(console, args);
            return;
        }
        const diff = (new Date()) - this.timeHolder;
        this.log(`Took ${Math.floor(diff/(1000*60*60))} hours, ${Math.floor(diff/(1000*60))} minutes and ${Math.floor(diff/(1000))} seconds ( ${diff} ms)`);
        this.time = new Date();
    }

    error() {
        const args = this._insertParamToArguments(arguments);
        if (typeof console.error === 'function') {
            console.error.apply(args);
            return;
        }

        this.log('--- ERROR ---');
        this.log(args);
    }

    err() {
        this.error.apply(this,arguments);
    }
    debug() {
        this.trace.apply(this,arguments);
    }

    warn() {
        const args = this._insertParamToArguments(arguments);
        if (typeof console.warn === 'function') {
            console.warn.apply(args);
            return;
        }

        this.log('--- WARN ---');
        this.log(args);
    }

}

if(window.debugState.backend){
    var globalLSConsole = new ConsoleShim('LSLOG');
    window.console.ls = globalLSConsole;
} else {
    var globalLSConsole = new ConsoleShim('LSLOG', true);
    window.console.ls = globalLSConsole;
}
