require('dotenv').config();

const fetch = require('node-fetch');
const Snoowrap = require('snoowrap');
const Snoostorm = require('snoostorm');

// Build Snoowrap and Snoostorm clients
const r = new Snoowrap({
    userAgent: 'reddit-bot-example-node',
    clientId: process.env.CLIENT_ID,
    clientSecret: process.env.CLIENT_SECRET,
    username: process.env.REDDIT_USER,
    password: process.env.REDDIT_PASS
});
const client = new Snoostorm(r);
/**
 * NFL Gif Bot Code
 */

// Configure options for stream: subreddit & results per query
const nflStreamOpts = {
    subreddit: 'nfl+nflgifbot+dyj+patriots',
    results: 40
};

// Create a Snoostorm CommentStream with the specified options
const comments = client.CommentStream(nflStreamOpts); // eslint-disable-line

// On comment, respond with NFL Gifs.
comments.on('comment', (comment) => {
	if (comment.body.startsWith('!patsbot')) {
        var replytext = '';
        // Format arguments: remove '!patsbot' and change spaces to +
        var gif_args = comment.body.replace('!patsbot ', '');
        gif_args.replace('\\_', '_');
        console.log(gif_args);
        var special_args = {
            patsbot: 'Hey, stop trying to break me, I have feelings too!',
            belichick: "1. [Curious Belichick](https://gfycat.com/ComfortableSmoggyHornedtoad) \n1. [Exasperated Belichick](https://gfycat.com/WillingBlaringAmericancicada)\n1. [Confused Belichick](https://gfycat.com/ImprobableFormalGerenuk) \n1. [Scoffing Belichick](https://gfycat.com/gentlecreamydungbeetle) \n1. [Angry Belichick](https://gfycat.com/LikableFortunateKiskadee)",
            buttfumble: "1. [The Butt Fumble](https://gfycat.com/InsidiousDetailedHermitcrab) \n1. [Butt Fumble Replay](https://gfycat.com/PortlySorrowfulAllensbigearedbat)",
        };
        if (gif_args in special_args) {
            var custom_message = special_args[gif_args];
            comment.reply(custom_message);
            console.log('Custom Response sent: ' + custom_message);
        }
        else {
            var gif_api = 'https://patriotsdynasty.info/reddit/patsbot/' + (gif_args.replace(/\s/g, '+'));
            console.log(gif_api);
        }
        // Get the data and return it as a comment reply.
        fetch(gif_api)
            .then((resp) => resp.json())
            .then(function(json) {
                json.forEach(function(obj) { 
                    console.log(obj.streamable);
                    if(obj.streamable == "True") {
                        replytext += "1. [" + obj.title + " (" + obj.season + " Week " + obj.week + ")](https://streamable.com/" + obj.gfycat + ") \n";
                    }
                    else {
                        replytext += "1. [" + obj.title + " (" + obj.season + " Week " + obj.week + ")](https://gfycat.com/" + obj.gfycat + ") \n";
                    }
                    
                });

            })
            .then(function(){
                
                if (replytext) {
                    replytext += "\nI'm a bot! Want to learn more about me? [Click here!](https://patriotsdynasty.info/patsbot-instructions)\n";
                    replytext += "\nGot a suggestion for the bot or a missing highlight? [Let me know.](https://patriotsdynasty.info/contact/feedback)";
                    comment.reply(replytext);   
                    console.log('Reply Text: ' + replytext); 
                }
                
                else {
                    comment.reply("Sorry, there's no highlights for those terms.\n I'm a bot! Want to learn more about me? [Click here!](https://patriotsdynasty.info/patsbot-instructions)\nGot a suggestion for the bot or a missing highlight? [Let me know.](https://patriotsdynasty.info/contact/feedback)");
                    console.log("Sorry, there's no highlights for those terms.");
                }
            })
            .catch(function(error) {
                replytext += 'Whoops, something went wrong!';
            });
    }
    if (comment.body.startsWith('!gifbot')) {
        var replytext = '';
        // Format arguments: remove '!gifbot' and change spaces to +
        var gif_args = comment.body.replace('!gifbot ', '');
        gif_args.replace('\\_', '_');
        console.log(gif_args);
        var special_args = {
            gifbot: 'Hey, stop trying to break me, I have feelings too!',
        };
        if (gif_args in special_args) {
            var custom_message = special_args[gif_args];
            comment.reply(custom_message);
            console.log('Custom Response sent: ' + custom_message);
        }
        else {
            var gif_api = 'http://dev-nfl-gifbot.pantheonsite.io/gifbot?search=' + (gif_args.replace(/\s/g, '+'));
            console.log(gif_api);
        }
        // Get the data and return it as a comment reply.
        fetch(gif_api)
            .then((resp) => resp.json())
            .then(function(json) {
                json.forEach(function(obj) { 
                    replytext += "1. [" + obj.title + " (" + obj.season + ")](https://gfycat.com/" + obj.gfycat + ") \n";
                });

            })
            .then(function(){
                
                if (replytext) {
                    replytext += "\nI'm a bot! Want to learn more about me? [Click here!](https://patriotsdynasty.info/patsbot-instructions)\n";
                    replytext += "\nGot a suggestion for the bot or a missing highlight? [Let me know.](https://patriotsdynasty.info/contact/feedback)";
                    comment.reply(replytext);   
                    console.log('Reply Text: ' + replytext); 
                }
                
                else {
                    comment.reply("Sorry, I couldn't find any highlights for those terms.\n I'm a bot! Want to learn more about me? [Click here!](https://patriotsdynasty.info/patsbot-instructions)\nGot a suggestion for the bot or a missing highlight? [Let me know.](https://patriotsdynasty.info/contact/feedback)");
                    console.log("Sorry, there's no highlights for those terms.");
                }
            })
            .catch(function(error) {
                replytext += 'Whoops, something went wrong!';
            });
    }
});

// Listen for [Highlight] submissions in the /r/nfl subreddit.
var submissionStream = client.SubmissionStream(nflStreamOpts);
   
submissionStream.on("submission", function(post) {
    // For each comment, 
if (post.link_flair_text == 'Highlights' || post.title.startsWith('[Highlight]')) {
    console.log(`New submission by ${post.author.name}: ${post.title} | ${post.url}`);
    if (post.title.startsWith('[Highlight]')) {
        var title = post.title.slice(11);
    }
    else {
        var title = post.title;
    }
    var post_gif = 'http://dev-nfl-gifbot.pantheonsite.io/bot/convert?title=' + title + '&link=' + post.url;
    console.log(post_gif);
    fetch(post_gif);
}
});