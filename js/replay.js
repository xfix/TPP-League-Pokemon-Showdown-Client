/* function updateProgress(done, a, b)
{
	if (!battle.paused) return;
	//$('#battle').html('<div class="playbutton"><button data-action="start">Play</button>'+(done?'':'<br />Buffering: '+parseInt(100*a/b)+'%')+'</div>');
} */
//setTimeout(function(){updateProgress(true)}, 10000);

if (window.soundManager) {
	soundManager.onready(function(){
		soundManager.ready = true;
		$('.soundchooser, .startsoundchooser').show();
	});
}

// Panels

var Topbar = Panels.Topbar.extend({
	height: 104
});

var ReplaySidebarPanel = Panels.StaticPanel.extend({
	minWidth: 300,
	maxWidth: 600
});

var ReplayPanel = Panels.StaticPanel.extend({
	minWidth: 960,
	maxWidth: 1180,
	moveTo: function() {
		Panels.Panel.prototype.moveTo.apply(this, arguments);
		this.$el.css('top', $(window).height() > 495 ? '104px' : '0');
	},
	events: {
		'click .chooser button': 'clickChangeSetting'
	},
	clickChangeSetting: function(e) {
		e.preventDefault();
		var $chooser = $(e.currentTarget).closest('.chooser');
		var value = e.currentTarget.value;
		this.changeSetting($chooser, value, $(e.currentTarget));
	},
	changeSetting: function(type, value, valueElem) {
		var $chooser;
		if (typeof type === 'string') {
			$chooser = this.$('.'+type+'chooser');
		} else {
			$chooser = type;
			type = '';
			if ($chooser.hasClass('colorchooser')) {
				type = 'color';
			} else if ($chooser.hasClass('soundchooser')) {
				type = 'sound';
			} else if ($chooser.hasClass('speedchooser')) {
				type = 'speed';
			}
		}
		if (!valueElem) valueElem = $chooser.find('button[value='+value+']');

		$chooser.find('button').removeClass('sel');
		valueElem.addClass('sel');

		switch (type) {
		case 'color':
			if (value === 'dark') {
				$(document.body).addClass('dark');
			} else {
				$(document.body).removeClass('dark');
			}
			break;

		case 'sound':
			var muteTable = {
				on: false, // this is kind of backwards: sound[on] === muted[false]
				off: true
			};
			this.battle.setMute(muteTable[value]);
			break;

		case 'speed':
			var speedTable = {
				fast: 8,
				normal: 800,
				slow: 2500,
				reallyslow: 5000
			};
			this.battle.messageDelay = speedTable[value];
			break;
		}
	},
	battle: null,
	errorCallback: function() {
		var replayid = this.$('input[name=replayid]').val();
		var m = /^([a-z0-9]+)-[a-z0-9]+-[0-9]+$/.exec(replayid);
		if (m) {
			this.battle.log('<hr /><div class="chat">This replay was uploaded from a third-party server (<code>' + Tools.escapeHTML(m[1]) + '</code>). It contains errors and cannot be viewed.</div><div class="chat">Replays uploaded from third-party servers can contain errors if the server is running custom code, or the server operator has otherwise incorrectly configured their server.</div>', true);
			this.battle.pause();
		}
	},
	updateContent: function() {
		this.$el.css('overflow-x', 'hidden');
		this.battle = new Battle(this.$('.battle'), this.$('.battle-log'));
		//this.battle.preloadCallback = updateProgress;
		this.battle.errorCallback = this.errorCallback.bind(this);
		this.battle.resumeButton = this.resume.bind(this);
		this.battle.setQueue((this.$('script.log').text()||'').replace(/\\\//g,'/').split('\n'));

		this.$('.battle').html('<div class="playbutton"><button data-action="start"><i class="icon-play"></i> Play</button><br /><br /><button data-action="startMuted" class="startsoundchooser" style="font-size:10pt;display:none">Play (music off)</button></div>');

		// this works around a WebKit/Blink bug relating to float layout
		var rc2 = this.$('.replay-controls-2')[0];
		if (rc2) rc2.innerHTML = rc2.innerHTML;

		if (window.soundManager && soundManager.ready) this.$('.soundchooser, .startsoundchooser').show();
	},
	pause: function() {
		this.$('.replay-controls').html('<button data-action="play"><i class="icon-play"></i> Play</button><button data-action="reset"><i class="icon-undo"></i> Reset</button> <button data-action="ff"><i class="icon-step-forward"></i> Skip to next turn</button> <button data-action="ffto"><i class="icon-fast-forward"></i> Go to turn...</button>');
		this.battle.pause();
	},
	play: function() {
		this.$('.battle .playbutton').remove();
		this.$('.replay-controls').html('<button data-action="pause"><i class="icon-pause"></i> Pause</button><button data-action="reset"><i class="icon-undo"></i> Reset</button> <button data-action="ff"><i class="icon-step-forward"></i> Skip to next turn</button> <button data-action="ffto"><i class="icon-fast-forward"></i> Go to turn...</button>');
		this.battle.play();
	},
	resume: function() {
		this.play();
	},
	reset: function() {
		this.battle.pause();
		this.$('.battle').html('<div class="playbutton"><button data-action="start"><i class="icon-play"></i> Play</button></div>');
		this.$('.battle-log').html('');
		this.$('.replay-controls').html('<button data-action="start"><i class="icon-play"></i> Play</button><button data-action="reset" disabled="disabled"><i class="icon-undo"></i> Reset</button>');
	},
	ff: function() {
		this.battle.skipTurn();
	},
	ffto: function() {
		this.battle.fastForwardTo(prompt('Turn?'));
	},
	start: function() {
		this.battle.reset();
		this.battle.play();
		this.$('.replay-controls').html('<button data-action="pause"><i class="icon-pause"></i> Pause</button><button data-action="reset"><i class="icon-undo"></i> Reset</button> <button data-action="ff"><i class="icon-step-forward"></i> Skip to next turn</button> <button data-action="ffto"><i class="icon-fast-forward"></i> Go to turn...</button>');
	},
	startMuted: function() {
		this.changeSetting('sound', 'off');
		this.start();
	}
});

var App = Panels.App.extend({
	topbarView: Topbar,
	states: {
		'*path': ReplaySidebarPanel, // catch-all default

		':replay': ReplayPanel,
		'search': ReplaySidebarPanel,
		'search:query': ReplaySidebarPanel,
		'search/:query': ReplaySidebarPanel
	},
});
var app = new App();
