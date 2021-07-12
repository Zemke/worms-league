const calc = require('./calc');
const fs = require('fs');
const path = require('path');
const { assert, assertTrue, test } = require('./test-util.js');

test('formatStats', () => {
  const stats = JSON.parse(fs.readFileSync(path.join(__dirname, '../resources/stats.json')));
  const actual = calc.formatStats(stats);
  try {
    const expected = {
      gameId: '765',
      home: 'Monster`tit4',
      winner: 'HOME',
      away: "NNN`Tade",
      date: '2020-12-20',
      time: '18:23:39',
    };
    assert(actual, expected);
  } catch {
    const expected = {
      gameId: '765',
      home: 'Monster`tit4',
      winner: "AWAY",
      away: "NNN`Tade",
      date: '2020-12-20',
      time: '18:23:39',
    };
    assert(actual, expected);
  }
});

test('reduceStats', () => {
  const stats = JSON.parse(fs.readFileSync(path.join(__dirname, '../resources/stats.json')));
  const statsArr = [stats, JSON.parse(JSON.stringify(stats))].map(s => calc.formatStats(s));
  const reducedStats = calc.reduceStats(statsArr)
  console.log('reducesStats', reducedStats);
  try {
    assert(reducedStats.home, 'Monster`tit4')
    assert(reducedStats.away, 'NNN`Tade')
  } catch {
    assert(reducedStats.away, 'Monster`tit4')
    assert(reducedStats.home, 'NNN`Tade')
  }
  assertTrue(reducedStats.homeScore < 3)
  assertTrue(reducedStats.homeScore >= 0)
  assertTrue(reducedStats.awayScore < 3)
  assertTrue(reducedStats.awayScore >= 0)
  assertTrue(reducedStats.homeScore + reducedStats.awayScore === 2)
});

