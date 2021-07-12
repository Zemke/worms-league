library(stringr)

#A)LOAD----

setwd("./logs")

a.files<- list.files()

a.raws<- list()
for(aux.file in 1:length(a.files)){
  a.raws[[aux.file]]<- read.delim(a.files[aux.file], sep="\t", h=F, quote="")
  a.raws[[aux.file]][[1]]<- str_remove_all(a.raws[[aux.file]][[1]], pattern = "\"|\'|")
}

rm(aux.file)

#clean logs----
#replays ended by quitting are removed
f_quality_check<- function(one.log){
  online.game<- grepl("^Game ID:", one.log[[1]][1])
  turn.starts<- sum(grepl("starts turn", one.log[[1]]))>0
  round.ends<- any(grepl("^End of round", one.log[[1]]))
  repeated.quit.line<- sum(grepl(" ... Game Ends - User Quit", one.log[[1]]))<2
  
  condition<- online.game & turn.starts & round.ends & repeated.quit.line
  
  return(condition)
}

a.qc<- sapply(a.raws, f_quality_check)

a.raws.clean<- a.raws[a.qc]
names(a.raws.clean)<- a.files[a.qc]

#log summary----
f_log_summary<- function(one.log){
  #remove these patterns
  to.remove<- c("Red:|Blue:|Green:|Yellow:|Magenta:|Cyan:|\\[Local Player\\]|\\[Host\\]")
  
  #p1
  p1.full<- one.log[6,]
  p1.local<- grepl("Local", p1.full)
  p1.full<- str_remove_all(string = p1.full, pattern = to.remove)
  p1.full<- str_replace_all(p1.full, "\\s+", " ")
  
  p1<- word(p1.full, 2)
  
  p1.team<- str_remove_all(p1.full, paste0(" ", p1, " as "))
  p1.team<- str_remove_all(p1.team, " ")
  p1.team<- str_replace_all(p1.team, "[^[:alnum:]]", "na")
  
  p1.row<- data.frame("full"= p1.full, "name"= p1, "team"= p1.team, "local"= p1.local)
  
  #p2
  p2.full<- one.log[7,]
  p2.local<- grepl("Local", p2.full)
  p2.full<- str_remove_all(string = p2.full, pattern = to.remove)
  p2.full<- str_replace_all(p2.full, "\\s+", " ")
  
  p2<- word(p2.full, 2)
  
  p2.team<- str_remove_all(p2.full, paste0(" ", p2, " as "))
  p2.team<- str_remove_all(p2.team, " ")
  p2.team<- str_replace_all(p2.team, "[^[:alnum:]]", "na")
  
  p2.row<- data.frame("full"= p2.full, "name"= p2, "team"= p2.team, "local"= p2.local)
  
  #make dataframe
  res<- rbind.data.frame(p1.row, p2.row)
  
  #check when round has ended
  end.line<- grep("^End of round", x = one.log[[1]])
  
  #check if victory is registered
  winner.line<- grep(pattern = " wins the round.| wins the match!", x = one.log[[1]])
  winner.line<- winner.line[length(winner.line)]
  
  #check if local quit
  local.quit<- any(grepl(pattern = " ... Game Ends - User Quit", x = one.log[[1]]))
  res$local.quit<- ifelse(res$local, local.quit, FALSE)
  
  #check if someone else quit
  quit.line<- grep(pattern = "disconnected due to quitting", x = one.log[[1]])
  quit.line<- one.log[[1]][quit.line]
  
  #if someone quit, check who
  res$disconnect.quit<- FALSE
  
  if(length(quit.line)>0){
    res$disconnect.quit<- c(grepl(p1, quit.line), grepl(p2, quit.line))
  }
  
  #consensus quit
  res$quit<- ifelse(res$local.quit | res$disconnect.quit, TRUE, FALSE)
  
  #if game did not end due to quitting, check winner
  res$winner<- 0
  
  if(!any(res$quit)){
    #get end of round
    end.round.line<- grep("^End of round", x=one.log[[1]])
    end.round.line<- end.round.line[length(end.round.line)]
    
    result.line<- grep(" wins the", x = one.log[[1]][end.round.line: length(one.log[[1]])])
    
    result.line<- grep("^Total game time elapsed:", x = one.log[[1]]) + 1
    result.line<- one.log[[1]][result.line]
    
    #check if round was drawn
    if(result.line == "The round was drawn."){
      res$winner<- c(0.5, 0.5)
    }
    
    #check if someone won
    someone.won<- grepl("wins the ", result.line)
    
    #if someone won, get team name and add info
    if(length(winner.line)>0){
      winner.team<- one.log[[1]][winner.line]
      winner.team<- str_remove_all(winner.team, " wins the round.| wins the match!")
      winner.team<- str_remove_all(winner.team, " ")
      winner.team<- str_replace_all(winner.team, "[^[:alnum:]]", "na")
      
      res$winner[grep(winner.team, res$team)]<- 1
    }
  }
  
  #add result
  res$res<- res$winner
  if(all(res$winner == c(0, 0))){
    res$res<- as.numeric(!res$quit)
  }
  
  #add date
  res$date<- word(one.log[[1]][2], 4)
  
  #add time
  res$time<- word(one.log[[1]][2], 5)
  
  return(res)
}

a.log.sum<- lapply(a.raws.clean, f_log_summary)
names(a.log.sum)

#games table----
a.games<- lapply(a.log.sum, function(one.summary){
  data.frame("p1"= one.summary$name[1],
             "p1w"= one.summary$res[1],
             "p2w"= one.summary$res[2],
             "p2"= one.summary$name[2],
             "date"= as.Date(one.summary$date[1], "%Y-%m-%d"),
             "time"= one.summary$time[1])
})

a.games<- do.call(rbind, a.games)
a.games<- cbind.data.frame("filename"= row.names(a.games), a.games)

#delete incomplete records
a.games.clean<- a.games[complete.cases(a.games),]

write.table(a.games.clean, "clean_games.txt", quote = FALSE)
