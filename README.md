# Worms League

Just post all your replays.<sup>1</sup>

<sup>1</sup> There should be a Windows in the background running process to
automatically submit all replays to Worms League.

## Reporting

1. Drop replay on the site
2. Send to WAaaS to extract logs
3. Make CSV for the R script
4. R script creates ranking
5. Website persists this ranking in a DB and shows on website

CSV to generate and pass to the R script:

```csv
"filename","p1","p1w","p2w","p2","date","time"
"2021-02-08 11.15.16 [Online Round 1] @NNNxDario, Rasi9nin - dario dagasst.log","NNNxDario",1,0,"Rasi9nin",2021-02-08,"11:15:16"
"2021-02-08 11.45.29 [Online Round 2] @NNNxDario, Rasi9nin - dario dagasst.log","NNNxDario",1,0,"Rasi9nin",2021-02-08,"11:45:29"
"2021-02-08 12.06.49 [Online Round 1] @NNNxDario, MIGHTY`taner - dario dagasst.log","NNNxDario",1,0,"MIGHTY`taner",2021-02-08,"12:06:49"
"2021-02-08 12.30.24 [Online Round 2] @NNNxDario, MIGHTY`taner - dario dagasst.log","NNNxDario",1,0,"MIGHTY`taner",2021-02-08,"12:30:24"
"2021-02-08 12.42.48 [Online Round 1] @NNNxDario, MIGHTY`taner - dario dagasst.log","NNNxDario",1,0,"MIGHTY`taner",2021-02-08,"12:42:48"
```

Drawn rounds can be computed as 0.5-0.5 result instead of 1-0 or 0-1

**Remember:** For the R scripts there's no such thing as a game with multiple rounds.
It's always just best of one games all the way.

