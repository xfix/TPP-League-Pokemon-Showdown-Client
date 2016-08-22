/**
 * 
 */

/*global window, $ */

if (window.AudioContext || window.webkitAudioContext) {

(function(window){
var audioCtx = new (window.AudioContext || window.webkitAudioContext)();

function SoundManager() {
    this.audioCtx = audioCtx;
    this.soundBank = {};
}
SoundManager.prototype = {
    soundBank : null,
    
    setup : function(options) {
        // compatability, not used.
    },
    
    /** Creates and returns a sound clip. */
    createSound : function(config) {
        if (!config) throw new Error("No configuration given for sound!");
        if (typeof config == "string") {
            config = { id : config, url : config, };
        }
        if (config.url === undefined) throw new Error("No url given for sound!");
        if (config.id === undefined) config.id = config.url;
        return (this.soundBank[config.id] = new Sound(config));
    },
    
    destroySound : function(id) {
        this.soundBank[id].stop();
        this.soundBank[id].unload();
        this.soundBank[id].distruct();
        delete this.soundBank[id];
        return true;
    },
    
    onready : function() {
        // compatibility.  
    },
};


function Sound(opts) {
    this.id = opts.id;
    this.url = opts.url;
    console.log("CREATE: "+this.id);
    
    this.__muteNode = audioCtx.createGain();
    if (audioCtx.createStereoPanner) {
        this.__panNode = audioCtx.createStereoPanner();
    }
    this.__volNode = audioCtx.createGain();
    this.__fadeNode = audioCtx.createGain();
    
    if (this.__panNode) {
        this.__muteNode.connect(this.__panNode);
        this.__panNode.connect(this.__volNode);
    } else {
        this.__muteNode.connect(this.__volNode);
    }
    this.__volNode.connect(this.__fadeNode);
    this.__fadeNode.connect(audioCtx.destination);
    
    this.__fadeNode.gain.setValueAtTime(1, 0);
    
    this.volume = (opts.volume || 50) / 100;
    this.pan = (opts.pan || 0) / 100;
    if (opts.loopstart && opts.loopend) {
        // If the loop start and end are given in milliseconds, convert to seconds.
        if (opts.loopstart > 1000 && opts.loopend > 2000) {
            opts.loopstart /= 1000;
            opts.loopend /= 1000;
        }
        this.loop = [opts.loopstart, opts.loopend];
    }
    
    this.load();
}
Sound.prototype = {
    id : null,
    url : null,
    
    loop : null,
    audiobuffer : null,
    
    playCount : 0, //handle load/play race conditions...
    startTime : 0,
    pauseOffset : null,
    
    // This isn't needed, really, but for compatability.
    distruct : function() {
        this.__fadeNode.disconnect();
        this.loop = null;
        this.audiobuffer = null;
        this.__loadPromise = null;
        this.__muteNode = null;
        this.__panNode = null;
        this.__sourceNode = null;
        this.__volNode = null;
        this.__fadeNode = null;
    },
    
    load : function() {
        var self = this;
        this.__loadPromise = new Promise(function(resolve, reject){
            var xhr = new XMLHttpRequest();
            xhr.open("GET", self.url, true);
            xhr.responseType = "arraybuffer";
            xhr.onload = function(e){
                resolve(xhr.response);
            };
            xhr.onerror = function(e){
                reject(e);
            };
            xhr.send();
        }).then(function(data){
            return audioCtx.decodeAudioData(data);
        }).then(function(data){
            self.audiobuffer = data;
            self.__loadPromise = null;
            return data;
        }).catch(function(e){
            self.__loadPromise = null;
            console.error("Error loading sound: ", e);
        });
        return this.__loadPromise;
    },
    
    unload : function() {
        this.audiobuffer = null;
    },
    
    play : function(time, offset, playDepth) {
        console.log("PLAY: "+this.id+" [count:"+(this.playCount+1)+"][depth:"+playDepth+"]");
        this.playCount++;
        if (this.__sourceNode) return true; //Don't double-play
        if (!this.audiobuffer) { //not loaded yet, can't play yet
            if (this.__loadPromise) {
                this.__loadPromise.then( this.play.bind(this, time, offset, this.playCount+1) ); //try again after things loads
            }
            return false;
        }
        if (playDepth !== undefined && playDepth !== this.playCount) return false;
        
        // `time` = how many seconds before starting playback
        time = (time || 0) + audioCtx.currentTime;
        // `offset` = how far into the song to start. "true" for random between loop points.
        if (this.loop && offset === true) {
            offset = (Math.random() * (this.loop[1] - this.loop[0])) + this.loop[0];
        } else {
            offset = offset || 0;
        }
        
        this.__sourceNode = audioCtx.createBufferSource();
        this.__sourceNode.buffer = this.audiobuffer;
        if (this.loop) {
            this.__sourceNode.loop = true;
            this.__sourceNode.loopStart = this.loop[0];
            this.__sourceNode.loopEnd = this.loop[1];
        }
        var self = this;
        this.__sourceNode.onended = function(){
            self.__sourceNode = null;
        };
        this.__sourceNode.connect(this.__muteNode);
        this.__sourceNode.start(time, offset);
        this.__fadeNode.gain.setValueAtTime(1, time+0.1);
        this.startTime = time - offset;
        this.pauseOffset = null;
        return true;
    },
    
    stop : function(time) {
        console.log("STOP: "+this.id+" [count:"+(this.playCount-1)+"]");
        this.playCount--;
        if (!this.__sourceNode) return true; //Can't double-stop
        
        // `time` = how many seconds before starting playback
        time = (time || 0) + audioCtx.currentTime;
        
        this.__fadeNode.gain.setValueAtTime(1, time+0.1);
        this.__sourceNode.stop(time);
        this.__sourceNode = null;
        return true;
    },
    
    pause : function() {
        console.log("PAUSE: "+this.id);
        if (!this.__sourceNode) return false; //can't pause while not playing
        
        this.pauseOffset = audioCtx.currentTime - this.startTime;
        if (this.loop) {
            this.pauseOffset -= this.loop[0];
            this.pauseOffset %= (this.loop[1] - this.loop[0]);
        }
        this.__sourceNode.stop();
        this.__sourceNode = null;
        return true;
    },
    
    resume : function() {
        console.log("RESUME: "+this.id);
        if (this.__sourceNode) return false; //can't resume while playing
        if (this.pauseOffset === null) return false; //can't resume if not paused
        
        var offset = this.pauseOffset + (this.loop)?this.loop[0]:0;
        return this.play(0, offset);
    },
    
    fadeOut : function(time, delay) {
        console.log("FADE: "+this.id);
        this.playCount--;
        if (!this.__sourceNode) return; //can't fade if we aren't playing
        delay = delay || 0;
        time = time || 2;
        this.__fadeNode.gain.setValueAtTime(1, audioCtx.currentTime+delay);
        this.__fadeNode.gain.linearRampToValueAtTime(0, audioCtx.currentTime+delay+time);
        this.__fadeNode.gain.setValueAtTime(1, audioCtx.currentTime+delay+time+0.5);
        this.__sourceNode.stop(audioCtx.currentTime+delay+time+0.1);
        // this.__sourceNode = null;
    },
    
    set onended(evt) {
        if (!this.__sourceNode) return; //can't assign when not playing
        this.__sourceNode.onended = evt;
    },
    
    get mute() {
        return this.__muteNode.gain.value < 0.5;
    },
    set mute(val) {
        this.__muteNode.gain.value = (!!val);
    },
    
    get volume(){
        return this.__volNode.gain.value;
    },
    set volume(val){
        this.__volNode.gain.value = val;
    },
    setVolume : function(vol) {
        this.volume = (vol/100);
        return this; //compatability chaining
    },
    
    get pan() {
        if (!this.__panNode) return 0;
        return this.__panNode.pan.value;
    },
    set pan(val) {
        if (!this.__panNode) return;
        this.__panNode.pan.value = val;
    },
    setPan : function(pan) {
        this.pan = (pan/100);
        return this; //compatability chaining
    },
};

window.SoundManager = SoundManager;
window.soundManager = new SoundManager;

})(window);

/*
} else if (window.Audio) {
(function(window){

function SoundManager() {
    this.soundBank = {};
}
SoundManager.prototype = {
    soundBank : null,
    setup : function(options) {},
    // Creates and returns a sound clip.
    createSound : function(config) {
        if (!config) throw new Error("No configuration given for sound!");
        if (typeof config == "string") {
            config = { id : config, url : config, };
        }
        if (config.url === undefined) throw new Error("No url given for sound!");
        if (config.id === undefined) config.id = config.url;
        return (this.soundBank[config.id] = new Sound(config));
    },
    destroySound : function(id) {
        this.soundBank[id].stop();
        this.soundBank[id].unload();
        this.soundBank[id].distruct();
        delete this.soundBank[id];
        return true;
    },
    onready : function() {},
};

function Sound() {
    this.id = opts.id;
    this.url = opts.url;
    
    
    
    this.load();
}
Sound.prototype = {
    id : null,
    
    distruct : function(){},
    load : function(){},
    unload : function(){},
    play : function(){},
    stop: function(){},
    pause: function(){},
    resume: function(){},
    fadeOut: function(){},
    set onended(evt) {},
    get mute() {},
    set mute(val) { return true; },
    get volume(){},
    set volume(val){ return 0; },
    setVolume : function() { return this; },
    get pan() {},
    set pan(val) { return  0; },
    setPan : function() { return this; },
};

window.SoundManager = SoundManager;
window.soundManager = new SoundManager;

})(window);

//*/
} else {  //No webaudio, make placeholder fallback
(function(window){
    
function SoundManager() {}
SoundManager.prototype = {
    soundBank : null,
    setup : function(options) {
    },
    /** Creates and returns a sound clip. */
    createSound : function(config) {
        console.warn("Could not create sound: no WebAudio context.");
        return new Sound();
    },
    destroySound : function(id) {
    },
    onready : function() {
    },
};

function Sound() {}
Sound.prototype = {
    id : null,
    
    distruct : function(){},
    load : function(){},
    unload : function(){},
    play : function(){},
    stop: function(){},
    pause: function(){},
    resume: function(){},
    fadeOut: function(){},
    set onended(evt) {},
    get mute() {},
    set mute(val) { return true; },
    get volume(){},
    set volume(val){ return 0; },
    setVolume : function() { return this; },
    get pan() {},
    set pan(val) { return  0; },
    setPan : function() { return this; },
};

window.SoundManager = SoundManager;
window.soundManager = new SoundManager;
})(window);
}