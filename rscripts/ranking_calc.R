library(openxlsx)
setwd("D:/Gdrive dagasst/2v2 mini league")
source("rank_calc_v4_functions.R")

#A)LOAD----
a.reports<- read.xlsx("reports.xlsx")
a.reports$date<- as.Date(a.reports$date, origin = "1899/12/30")

#make new object to modify
a.games<- a.reports
names(a.games)<- c("p1", "p1w", "p2w", "p2", "date")

#add no scale
a.games$p1w.s<- a.games$p1w
a.games$p2w.s<- a.games$p2w

#calculate ranking----
#exp rate
#regulates how quickly you stop gaining points from beating the same player over and over again
a.exp.base<- 2.5

#minimum game worth factor
#games are worth less as their get older, this puts a cap to how low a game worth can go due it age
#(0-1], at 1 age has no effect on a game worth
a.min.factor<- 1

#nudge factor
#this adds a fixed number to the proportional reward players get from all their games
#a value of 0 will cause players with 0 rounds won to have 0 points and also give 0 points to people who beat them
#lower values make noob-bashing less worthy.
a.nudge<- 0.01

#number of recalculation cycles
#5 is usually more than enough
a.cycles<- 5

#top.bonus
#higher than 1 increases the reward for winning against players with many points
#therefore noob-bashing becomes less effective as a climbing strategy
#between 0 and 1 increases the reward for winning against players with few points
#0 makes beating a top player reward the same as beating a bottom player.
a.top.bonus<- 1

#format as table
a.rounds<- f_make_rounds_matrix(trimmed.data = a.games)

#calculate ranks
a.final.rank<- f_rank_wrap(rounds.table = a.rounds$scaled.rounds,
                           prop.table = a.rounds$unscaled.prop,
                           rounds.raw = a.rounds$unscaled.rounds,
                           exp.base = a.exp.base,
                           min.factor = a.min.factor,
                           nudge = a.nudge,
                           n.cycles = a.cycles,
                           top.bonus = a.top.bonus,
                           top.player = colnames(a.rounds$scaled.rounds)[1])

#recalculate with the correct top.player
a.top<- row.names(a.final.rank$player.info)[which.max(a.final.rank$player.info$points)]

a.final.rank<- f_rank_wrap(rounds.table = a.rounds$scaled.rounds,
                           prop.table = a.rounds$unscaled.prop,
                           rounds.raw = a.rounds$unscaled.rounds,
                           exp.base = a.exp.base,
                           min.factor = a.min.factor,
                           nudge = a.nudge,
                           n.cycles = a.cycles,
                           top.bonus = a.top.bonus,
                           top.player = a.top)

#export results----
a.rank.export<- cbind.data.frame("Team"= row.names(a.final.rank$player.info), a.final.rank$player.info)
a.rank.export<- a.rank.export[order(a.rank.export$rank),]
a.rank.export$points<- round(a.rank.export$points, 1)

a.rounds.export<- cbind.data.frame("Player"= row.names(a.rounds$unscaled.rounds), a.rounds$unscaled.rounds)


#workbook----
a.team.width<-20

#workbook to adjust colwidths and style
a.export<- createWorkbook()

addWorksheet(a.export, "Ranking")
addWorksheet(a.export, "Games")
addWorksheet(a.export, "Summary")

modifyBaseFont(a.export, fontSize = 24, fontName = "Calibri")

#ranking sheet----

writeDataTable(a.export, "Ranking", a.rank.export,
               withFilter = FALSE,
               tableStyle = "TableStyleLight1")

setColWidths(a.export, "Ranking", 
             cols = 1:ncol(a.rank.export), 
             widths = c(a.team.width, 5, 5, 10, 5))


a.nrows<- 0:nrow(a.rank.export) + 1
a.ncols<- 1:ncol(a.rank.export)

addStyle(wb = a.export, sheet = 1, rows = a.nrows, cols = a.ncols, gridExpand = TRUE,
              style = createStyle(halign = "center"), stack = TRUE)

#reports sheet----
a.reports.export<- a.reports[order(a.reports$date, decreasing = TRUE),]

writeData(a.export, "Games", a.reports.export,
               withFilter = FALSE,
               colNames = FALSE)

setColWidths(a.export, "Games", 
             cols = 1:ncol(a.reports.export), 
             widths = c(a.team.width, 5, 5, a.team.width, 10))


addStyle(wb = a.export, sheet = 2, rows = 1:nrow(a.reports.export), cols = 1:5, gridExpand = TRUE,
         style = createStyle(halign = "center"), stack = TRUE)

#summary sheet----

writeData(a.export, "Summary", a.rounds.export,
          withFilter = FALSE)

addStyle(wb = a.export, sheet = 3, 
         rows = 0:nrow(a.rounds.export)+1, 
         cols = 0:ncol(a.rounds.export)+1,
         gridExpand = TRUE,
         style = createStyle(halign = "center"),
         stack = TRUE)

#save workbook----

saveWorkbook(a.export, "results.xlsx",  overwrite =TRUE)

View(a.rank.export)
View(a.reports.export)
