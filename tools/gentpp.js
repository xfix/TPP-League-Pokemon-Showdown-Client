var readline = require('readline');
var rl = readline.createInterface({input: process.stdin});
var pokedex = require('../data/pokedex.js').BattlePokedex;

var allowedMons = {};

var notMons = {
	'': 1, 'this': 1, please: 1, t: 1, additional: 1, only: 1, you: 1,
	notes: 1, and: 1, list: 1, these: 1, 'if': 1, primal: 1, mega: 1, there: 1
};

function toId(name) {
	return name.toLowerCase().replace(/[^a-z0-9]/g, "");
}

function processPrevos(mon) {
	var entry = pokedex[mon];
	var prevo = entry.prevo;
	if (prevo) {
		allowedMons[prevo] = true;
		processPrevos(prevo);
	}
}

function processEvos(mon) {
	var entry = pokedex[mon];
	var evos = (entry.evos || []).concat(entry.otherFormes || []);
	allowedMons[mon] = true;
	evos.forEach(processEvos);
}

rl.on('line', function (line) {
	var mon = toId((/nidoran m|farfetch'd|mr. mime|\w+/i.exec(line) || [''])[0]);
	if (notMons[mon]) {
		return;
	}
	processPrevos(mon);
	processEvos(mon);
});

rl.on('close', function (line) {
	console.log("exports.BattleTPP = " + JSON.stringify(allowedMons));
})
