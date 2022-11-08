The first general purpose pure ladder for Worms Armageddon.

# Relative Ranking System

Ranking system for ladder rankings where games are won in best out of X rounds.

## Problem

In the public world one is accustomed to absolute ranking system.
These are self-contained perfect.
Everyone plays everyone the same amount of times.
Therefore there's no need in complexity to assess a points pattern other than
addition.
The fixtures are inherently perfect.

This is not typically the case in online games where there's to fixed schedule
and not everyone is necessarily playing everyone the same amount of times.
So players may never clash, others multiple times.
An absolute ranking would make players end up top who win most of their games.
In other words, possible the most active or the one playing the weakest players.

## Solution

Therefore the series of a user has to be evaluated within a context that's
**relative** to all the other contenders in.

The most blatant example is a victory against a higher ranked other player
should get one more points than against a lower ranked player.

## Increasingly Relative

In a relative ranking system the points of a user are determined by the points
of the opponents one has played against. The question is how to you initially
determine the points of another user when they're not yet ranked.

### Starting Absolute

The most fundamental information that an absolute ranking could be generated
from is the total won rounds. One gets one points per won round.

This is now an absolute state. The idea of the relative ranking system is to
put this absolute rating of each players into perspective — into context.

### Relativizers

Looking at the number of won rounds of each player under which circumstances
were they actually attained? \
One is more likely to add won rounds based on these factors:

* level of opponents
* number of rounds
* total number of rounds

These three factors are relative among themselves again. For instance the more
number of rounds against the same opponent of a weaker skill level, the more
likely one is to gain won rounds and vice versa. \
If one is generally playing for rounds, it's more likely to accumulate won
rounds, too.

These are the three main factors used in the relative ranking system.
These can be referenced to put the absolute value of won rounds into a context.
Therefore they relativize an absolute number and are thus called "relativizers."

### Relativization Steps

Each calculation of a relative ranking starts with an absolute ranking.
They're put into relation using relativizers.

Since two perspectives -- an absolute and a relative -- are put against each
other, it is necessary to balance them out.

The more relativity is "applied" to the absolute information, the less the
original absolute value has any weight in the final outcome.
Potentially leading to an entirely unfounded result as the relativization
process to relativize itself more and more.

The relative ranking system applies relativization steps. With each new
step the previous value is relativized further. Each output is the input
of the next step. As aforementioned the initial input is the absolute value of
won rounds.

Each step is the same relativization algorithm applied to the input.

## Algorithm

### Configuration

To further fine-tune the balancing of relativization relative ranking system
uses two configuration parameters.

#### `relSteps`

`relSteps` is used in a formula to determine the total number of relativization
steps. \
Consider `a` to be `relSteps` and `x` is the maximum total number of
steps played of any user.

$$ S = \max(\min(-1+\log(x*.13)*a,21),1) $$

`S` is solved for the number of relativization steps to take.

Assuming `relSteps` is `36`, it would generate the following relativization
steps per maximum rounds played of any user.

![Relativization steps per max rounds played of any user](1_relxg.png)

The user with the maximum number of rounds played on the y-axis and what number
of relativization steps it would cause on the y-axis.

The graph shows how the number of steps rises logarithmically.
The greater `relSteps` the steeper the rise. Hence a greater `relSteps` factor
causes the relativization to be more effect thus weighing in more in the
balancing ob the absolute number versus the relativized result.

There has got to be at least one relativization step and `21` at most.
The result is rounded down.

`20` for the `relSteps` parameter and maxing out at `21` have proven to deliver the
best results. More on that later.

#### `relRel`

`relRel` is used in a formula to influence the impact of each individual
relativization step.

Each 

$$ R_1 = R_0 * {\sum_{i=1}^{3}{rel_i} + a \over 4} $$


