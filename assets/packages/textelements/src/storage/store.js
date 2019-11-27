import Vue from 'vue';
import Vuex from 'vuex';
import VuexPersistence from 'vuex-persist';
import VueLocalStorage from 'vue-localstorage';

import mutations from './mutations';
import actions from './actions';
import getters from './getters';
import statePreset from './state';

Vue.use(VueLocalStorage);
Vue.use(Vuex);


export default function(sid){
    const vuexLocal = new VuexPersistence({
        key: 'lstextedit_'+sid,
        saveState: window.LS.localStorageInterface.getSaveState('lstextedit_'+sid),
        storage: window.LS.localStorageInterface.getLocalStorage()
    });
    
    return new Vuex.Store({
        state: statePreset,
        plugins: [
            vuexLocal.plugin
        ],
        mutations,
        actions,
        getters
    });
}
