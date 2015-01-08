#!/bin/sh
cd ..
OPTIONS='-c --screw-ie8'
uglifyjs js/lib/jquery-2.1.0.min.js js/lib/jquery-cookie.js js/lib/autoresize.jquery.min.js js/lib/jquery.json-2.3.min.js js/lib/soundmanager2-nodebug-jsmin.js js/lib/html-sanitizer-minified.js js/lib/lodash.compat.js js/lib/backbone.js js/lib/d3.v3.min.js js/colors.js js/battledata.js data/pokedex-mini.js data/typechart.js js/battle.js js/lib/sockjs-0.3.4.min.js js/client.js js/client-mainmenu.js js/client-teambuilder.js js/client-ladder.js js/client-chat.js js/client-chat-tournament.js js/client-battle.js js/client-rooms.js js/storage.js data/graphics.js -o js/stage1.js $OPTIONS
uglifyjs data/learnsets-g6.js js/lib/jquery.slider.min.js data/pokedex.js data/formats-data.js data/moves.js data/items.js data/abilities.js js/utilichart.js data/aliases.js -o js/stage2.js $OPTIONS
