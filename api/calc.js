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
  const users = stats.turns
      .map(t => t.user)
      .filter((val, idx, arr) => arr.indexOf(val) === idx)
  if (users.length > 2) throw Error(`There are ${users.length} users but max is 2.`);
  users.reduce((acc, team, idx) => {
    if (team in acc && acc[team].includes(curr)) {
      acc[curr].push(curr)
    }
    return acc;
  }, {});
  return {
    home: users[0],
    homeScore: 0, // TODO
    awayScore: 1, // TODO
    away: users[1],
    date: '2020-12-20',
    time: '18:23:39',
  }
}

function toCsv(stats) {
  let builder = firstRow;
  for (const stat in stats) {
    //builder += stat. ; TODO
  }
  return builder;
}

module.exports = { toCsv, formatStats };

