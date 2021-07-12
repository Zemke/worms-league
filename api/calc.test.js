const calc = require('./calc');
const fs = require('fs');
const path = require('path');
const { assert, test } = require('./test-util.js');

test('formatStats', () => {
  const stats = JSON.parse(fs.readFileSync(path.join(__dirname, '../resources/stats.json')));
  const actual = calc.formatStats(stats);
  try {
    const expected = {
      gameId: '765',
      home: 'Monster`tit4',
      homeWon: true,
      away: "NNN`Tade",
      date: '2020-12-20',
      time: '18:23:39',
    };
    assert(actual, expected);
  } catch {
    const expected = {
      gameId: '765',
      home: 'Monster`tit4',
      homeWon: false,
      away: "NNN`Tade",
      date: '2020-12-20',
      time: '18:23:39',
    };
    assert(actual, expected);
  }
});

