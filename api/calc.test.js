const calc = require('./calc');
const fs = require('fs');
const path = require('path');

const stats = JSON.parse(fs.readFileSync(path.join(__dirname, '../resources/stats.json')));

const actual = calc.formatStats(stats);
const expected = {
  home: 'Monster`tit4',
  homeScore: 0,
  awayScore: 1,
  away: "NNN`Tade",
  date: '2020-12-20',
  time: '18:23:39',
};

console.assert(JSON.stringify(actual) === JSON.stringify(expected), { actual, expected });

console.info('actual', actual);

