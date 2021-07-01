#set working directory to the script location
#make sure the rounds file is in the working directory

#unplayed games must figure as 0-0 and not NA


#FUNCTIONS----
#loads the list of games and formats date
f_load_gamelist<- function(file.path){
  the.table<- read.table(file = file.path, row.names=NULL, h=TRUE)
  names(the.table)<- c("p1", "p1w", "p2w", "p2", "date")
  the.table$date<- as.Date(the.table$date)

  return(the.table)
}

#subselects the list of games according to date, adds scaled rounds according to age of the game
#needs output from f_load_gamelist
#date has to be formated as date %Y-%m-%d
f_trim_and_scale<- function(formated.input, date.start, date.end, min.factor){
  #subset data
  season.length<- as.numeric(date.end - date.start)
  game.index<- which(formated.input$date >= date.start & formated.input$date <= date.end)
  trimmed.input<- formated.input[game.index,]
  
  #add variables
  trimmed.input$age<- as.integer(max(trimmed.input$date) - trimmed.input$date)
  trimmed.input$age.scaled<- trimmed.input$age / season.length
  trimmed.input$scaling.factor<- 1 - trimmed.input$age.scaled
  trimmed.input$scaling.factor[which(trimmed.input$scaling.factor < min.factor)]<- min.factor
  
  trimmed.input$p1w.s<- trimmed.input$p1w * trimmed.input$scaling.factor
  trimmed.input$p2w.s<- trimmed.input$p2w * trimmed.input$scaling.factor
  
  rownames(trimmed.input)<- NULL
  
  return(trimmed.input)
}

#needs output from f_trim_and_scale
#creates the matrix of rounds and scaled rounds to perform the ranking calculations
f_make_rounds_matrix<- function(trimmed.data){
  players<- sort(unique(c(trimmed.data$p1, trimmed.data$p2)))
  empty.matrix<- matrix(0,nrow=length(players), ncol=length(players))
  rownames(empty.matrix)<- players
  colnames(empty.matrix)<- players
  
  unscaled.matrix<- empty.matrix
  for(aux.index in 1:nrow(trimmed.data)){
    unscaled.matrix[trimmed.data$p1[aux.index], trimmed.data$p2[aux.index]] <- trimmed.data$p1w[aux.index] + unscaled.matrix[trimmed.data$p1[aux.index], trimmed.data$p2[aux.index]]
    unscaled.matrix[trimmed.data$p2[aux.index], trimmed.data$p1[aux.index]] <- trimmed.data$p2w[aux.index] + unscaled.matrix[trimmed.data$p2[aux.index], trimmed.data$p1[aux.index]]
  }
  
  unscaled.total<- unscaled.matrix + t(unscaled.matrix)
  unscaled.prop<- unscaled.matrix / unscaled.total
  
  scaled.matrix<- empty.matrix
  for(aux.index in 1:nrow(trimmed.data)){
    scaled.matrix[trimmed.data$p1[aux.index], trimmed.data$p2[aux.index]] <- trimmed.data$p1w.s[aux.index] + scaled.matrix[trimmed.data$p1[aux.index], trimmed.data$p2[aux.index]]
    scaled.matrix[trimmed.data$p2[aux.index], trimmed.data$p1[aux.index]] <- trimmed.data$p2w.s[aux.index] + scaled.matrix[trimmed.data$p2[aux.index], trimmed.data$p1[aux.index]]
  }
  
  scaled.total<- scaled.matrix + t(unscaled.matrix)
  scaled.prop<- scaled.matrix / scaled.total
  
  results<- list("unscaled.rounds"= unscaled.matrix,
                 "unscaled.total" = unscaled.total,
                 "unscaled.prop" = unscaled.prop,
                 "scaled.rounds"= scaled.matrix,
                 "scaled.total"= scaled.total,
                 "scaled.prop" = scaled.prop)
  
  return(results)
}

#calculate base points
#exp.base is a league parameter
#rounds.table should be the scaled rounds output from f_make_rounds_matrix
f_points<- function(rounds.table, exp.base){
  won.rounds<- unlist(rounds.table)
  lost.rounds<- unlist(t(rounds.table))
  total.rounds<- won.rounds + lost.rounds

  f_vectorized_exp<- function(won.rounds, total.rounds, exp.base){
    
    #only calculate points if
    points<- 0
    if(won.rounds != 0){
      prop<- (won.rounds) / (total.rounds)
      points<- prop * (1-exp.base^(-total.rounds))
    }
    
    if(total.rounds==0){
      points<-NA
    }
    
    return(points)
  }
  
  points.vector<- mapply(f_vectorized_exp, won.rounds, total.rounds, exp.base)
  
  return(points.vector)
}

#id is a player name
#prop.table should be the unscaled.prop output from f_make_rounds_matrix
#exp base is league parameter
#min.factor is the min.factor of f_trim_and_scale
#so that indirect games are as scaled as the oldest games possible
f_indirect<- function(id, prop.table, rounds.raw, exp.base, min.factor){
  
  other.ids<- colnames(prop.table)
  
  points<- sapply(other.ids, function(one.other.id){
    
    #base indirect points = 0
    aux.points<- 0
    
    #condition no rounds played
    condition<- rounds.raw[id, one.other.id] == 0 &
      rounds.raw[one.other.id, id] == 0 &
      id != one.other.id
    
    #if no rounds between two players, try calculate indirect points
    if(condition){
      
      #isolate players
      subtable<- prop.table[c(id, one.other.id),]
      
      #calculate ratios
      ratios<- subtable[id,] / colSums(subtable)
      mean.ratio<- mean(ratios, na.rm=TRUE)
      
      if(!is.na(mean.ratio)){
        aux.points<- mean.ratio * (1-exp.base^(-0.25)) * min.factor
      }

    }
    
    return(aux.points)
  })
  
  #order as in the prop table
  points<- points[colnames(prop.table)]
  names(points)<- colnames(prop.table)
  
  return(points)
  
}

#wrapper for points and indi points
#nudge adds directly to the final points table so that no player has 0 points
f_final_points<- function(rounds.table, prop.table, rounds.raw, exp.base, min.factor, nudge){
  
  #calculate base points
  base.points<- f_points(rounds.table, exp.base)
  base.points<- matrix(base.points, ncol=nrow(rounds.table))
  dimnames(base.points)<- dimnames(rounds.table)

  #calculate indirect points
  indi.points<- lapply(row.names(rounds.table), f_indirect, prop.table, rounds.raw, exp.base, min.factor)
  indi.points<- do.call(rbind, indi.points)
  indi.points[is.na(indi.points)]<- 0
  
  #final points
  final.points<- base.points
  final.points[is.na(final.points)]<- 0
  final.points<- final.points + indi.points + nudge

  #results
  res<- list("props"= prop.table, "base.points" = base.points, "indi.points"= indi.points, "final.points"= final.points)
  
  res<- lapply(res, function(one.table){
    rownames(one.table)<- rownames(rounds.table)
    return(one.table)
  })
  
  return(res)
}

f_add_godlike<- function(final.points, nudge, top.player){
  godlike.won<- rep(1, ncol(final.points)) + nudge
  names(godlike.won)<- colnames(final.points)
  godlike.won[top.player]<- nudge
  
  final.godlike<- rbind("godlike"=godlike.won, final.points)

  godlike.lost<- rep(nudge, nrow(final.godlike))
  final.godlike<- cbind("godlike"=godlike.lost, final.godlike)
  
  return(final.godlike)
}

#function for one rank cycle, takes as input a previous ranking with points per player
#top.bonus higher than 1 increases the reward for beating players that have more points, reduces the reward from noob bashing.
f_rank_cycle<- function(player.scores, final.points, top.bonus){
  #transformation matrix
  trans.mat<- t(final.points)
  trans.mat[is.na(trans.mat)]<- 0
  
  new.scores<- player.scores^top.bonus %*% trans.mat
  new.scores<- new.scores[1,]
  
  #godlike score
  scaled.scores<- new.scores / new.scores["godlike"]
  
  return(scaled.scores)
}

#use rank cycle function to loop
f_rank_loops<- function(final.points, n.cycles, top.bonus){
  #first rank
  base.scores<- f_rank_cycle(player.scores = rep(1, nrow(final.points)),
                             final.points,
                             top.bonus)
  
  #cycles
  scores.list<- list()
  scores.list[[1]]<- base.scores
  
  for(aux.cycle in 2:n.cycles){
    scores.list[[aux.cycle]]<- f_rank_cycle(player.scores = scores.list[[aux.cycle-1]],
                                            final.points,
                                            top.bonus)
  }
  
  #scores table
  scores.table<- data.frame(t(do.call(rbind, scores.list)))
  names(scores.table)<- paste0("cycle", 1:n.cycles)
  
  #ranks table
  ranks.table<- scores.table
  ranks.table[]<- lapply(1/scores.table, rank)
  
  #res
  res<- list("scores"= scores.table, "ranks"= ranks.table)
}

#final ranking wrapper
f_rank_wrap<- function(rounds.table, prop.table, rounds.raw, exp.base, min.factor, nudge, top.bonus, n.cycles, top.player){
  
  #calculate points table
  final.points<- f_final_points(rounds.table, prop.table, rounds.raw, exp.base, min.factor, nudge)
  
  final.points$final.godlike<- f_add_godlike(final.points$final.points, nudge, top.player)
  
  #calculate rankings
  final.rankings<- f_rank_loops(final.points = final.points$final.godlike, n.cycles, top.bonus)
  
  #get rankings without the godlike player
  final.scores<- round(100 * final.rankings$scores[-1,n.cycles], 3)
  final.ranks<- final.rankings$ranks[-1,n.cycles] -1
  
  #summarize information
  player.info<- data.frame("won"= rowSums(rounds.raw),
                           "lost"= rowSums(t(rounds.raw)),
                           "points"= final.scores,
                           "rank"= final.ranks)
  
  #get full results
  res<- list("points"= final.points, "ranking.calc"= final.rankings, "player.info"= player.info)
  
  return(res)
}