// ## Reporting
//
// 1. Drop replay on the site
// 2. Send to WAaaS to extract logs
// 3. Make CSV for the R script
// 4. R script creates ranking
// 5. Website persists this ranking in a DB and shows on website
//
// CSV to generate and pass to the R script:
//
// ```csv
// "filename","p1","p1w","p2w","p2","date","time"
// "2021-02-08 11.15.16 [Online Round 1] @NNNxDario, Rasi9nin - dario dagasst.log","NNNxDario",1,0,"Rasi9nin",2021-02-08,"11:15:16"
// "2021-02-08 11.45.29 [Online Round 2] @NNNxDario, Rasi9nin - dario dagasst.log","NNNxDario",1,0,"Rasi9nin",2021-02-08,"11:45:29"
// "2021-02-08 12.06.49 [Online Round 1] @NNNxDario, MIGHTY`taner - dario dagasst.log","NNNxDario",1,0,"MIGHTY`taner",2021-02-08,"12:06:49"
// "2021-02-08 12.30.24 [Online Round 2] @NNNxDario, MIGHTY`taner - dario dagasst.log","NNNxDario",1,0,"MIGHTY`taner",2021-02-08,"12:30:24"
// "2021-02-08 12.42.48 [Online Round 1] @NNNxDario, MIGHTY`taner - dario dagasst.log","NNNxDario",1,0,"MIGHTY`taner",2021-02-08,"12:42:48"
// ```
//
// Drawn rounds can be computed as 0.5-0.5 result instead of 1-0 or 0-1

const firstRow = "\"filename\",\"p1\",\"p1w\",\"p2w\",\"p2\",\"date\",\"time\"";

function formatStats(stats) {
  const users = assertUsers(stats.turns)
  if (users.length !== 2) throw Error(`There should be two users but found: ${users}`);
  const homeWon = new Boolean(Math.ceil(Math.random()*2-1)); // TODO find game winner
  return {
    gameId: stats.gameId,
    home: users[0],
    winner: homeWon ? 'HOME' : 'AWAY', // TODO Could also be DRAW
    away: users[1],
    date: '2020-12-20',
    time: '18:23:39',
  }
}

function reduceStats(statsArr) {
  const users = statsArr.reduce((acc, s) => {
    if (!acc.includes(s.home)) acc.push(s.home);
    if (!acc.includes(s.away)) acc.push(s.away);
    return acc;
  }, []);
  const scores = {
    [users[0]]: 0,
    [users[1]]: 0,
  }
  statsArr.reduce((acc, stats1) => {
    if (stats1.winner === 'HOME') {
      scores[stats1.home] += 1;
    } else if (stats1.winner === 'AWAY') {
      scores[stats1.away] += 1;
    }
    return acc;
  }, {});
  return {
    home: users[0],
    homeScore: scores[users[0]],
    away: users[1],
    awayScore: scores[users[1]],
  }
}

function toCsv(stats) {
  let builder = firstRow;
  for (const stat in stats) {
    //builder += stat. ; TODO
  }
  return builder;
}

function assertUsers(turns) {
  const users = turns
      .map(t => t.user)
      .filter((val, idx, arr) => arr.indexOf(val) === idx)
  if (users.length !== 2) throw Error(`There should be two users but found: ${users}`);
  return users;
}

module.exports = { toCsv, formatStats, reduceStats };

