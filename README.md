
# Introducing PatsBot

## What is it?

PatsBot is a Reddit bot that will return a list of highlight gifs as a reply to any comment that starts with `!patsbot`.

## Arguments are Your Friend

1. Players: `!patsbot brady` will return all highlights involving Tom Brady.
   * You only need to use the player's last name, and PatsBot will usually figure out who you're talking about.
   * If you want to use a full name, use a dash as a separator: `!patsbot kyle-brady`.
2. Opponents: `!patsbot broncos` will return all highlights where the opponent was the Denver Broncos.
3. Season: `!patsbot 2007` will return all highlights in the 2007 season.
4. Week: `!patsbot week 1` will return all highlights that happened in week 1.
   * Note: playoff weeks are numerical too:
      * Super Bowl: week 21
      * Championship: week 20
      * Divisional: week 19
      * Wildcard: week 18

## Usage Examples

Arguments can be chained together. Here are some examples:

* `!patsbot moss 2007 dolphins` will return all highlights of Randy Moss in the 2007 season against the Dolphins.
* `!patsbot brady week 21` will return all highlights of Tom Brady in any Super Bowl.
* `!patsbot brady moss 2007 dolphins` will return all highlights that include both Brady AND Moss in the 2007 season against the Dolphins.

## Current Limitations

Right now we don't have highlights for every game. I've been working on helping out /u/timnog with creating these; right now we've covered the following:

* Full Seasons:
   * 2017
   * 2016
   * 2015 (except week 17, weirdly)
   * 2011
   * 2001
* Sporadic Games:
   * 2010 (Weeks 8, 11, 14)
   * 2009 (Weeks 6, 16)
   * 2007 (Weeks 6, 7, 8 11, 13, 14, AFC Div & Champ)
   * 2006 (Week 12, AFC Divisional)
   * 2005 (Week 17)
   * 2004 (AFC Divisional)
   * 2003 (Weeks 9, 11, 14, 16 and all playoffs)
   * 2002 (Weeks 3, 10, 13)​

## For Developers

If you'd like to return Patriots gifs yourself, you'll just need to build a query that hits this endpoint: https://patriotsdynasty.info/reddit/patsbot/. Add the arguments to the end of the url separated by + and it will return a JSON object with a list of gifs and their relevant information.

For example, if you hit https://patriotsdynasty.info/reddit/patsbot/brady+2007+cowboys, the system will respond with the following JSON object:
```javascript
[
   {
      "id": "292",
      "gfycat": "RegalBraveKingfisher",
      "title": "Brady to Moss 6yd TD",
      "players": "Tom Brady, Randy Moss",
      "field_opponent": "Dallas Cowboys",
      "season": "2007",
      "week": "6"
   },
   {
      "id": "293",
      "gfycat": "LimitedDeterminedAgama",
      "title": "Brady to Welker 25yd TD",
      "players": "Tom Brady, Wes Welker",
      "field_opponent": "Dallas Cowboys",
      "season": "2007",
      "week": "6"
   },
   {
      "id": "294",
      "gfycat": "ColorlessAmusingEquine",
      "title": "Brady to Welker 12yd TD",
      "players": "Tom Brady, Wes Welker",
      "field_opponent": "Dallas Cowboys",
      "season": "2007",
      "week": "6"
   },
   {
      "id": "295",
      "gfycat": "HelpfulGranularBlesbok",
      "title": "Brady to (Kyle) Brady 1yd TD",
      "players": "Tom Brady, Kyle Brady",
      "field_opponent": "Dallas Cowboys",
      "season": "2007",
      "week": "6"
   },
   {
      "id": "296",
      "gfycat": "KindheartedVengefulFlamingo",
      "title": "Brady to Stallworth 69yd TD",
      "players": "Tom Brady, Donte Stallworth",
      "field_opponent": "Dallas Cowboys",
      "season": "2007",
      "week": "6"
   }
]
```