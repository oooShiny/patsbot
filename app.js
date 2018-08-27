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

// Configure options for stream: subreddit & results per query
const streamOpts = {
    subreddit: 'dyj+patriots',
    results: 25
};

// Create a Snoostorm CommentStream with the specified options
const comments = client.CommentStream(streamOpts); // eslint-disable-line

// On comment, perform whatever logic you want to do
comments.on('comment', (comment) => {
    if (comment.body.startsWith('!patsbot')) {
        var replytext = '';
        // Format arguments: remove '!patsbot' and change spaces to +
        var gif_args = comment.body.replace('!patsbot ', '');
        gif_args.replace('\\_', '_');
        console.log(gif_args);
        var gif_api = 'https://patriotsdynasty.info/reddit/patsbot/' + (gif_args.replace(/\s/g, '+'));
        console.log(gif_api);
        // Get the data and return it as a comment reply.
        fetch(gif_api)
            .then((resp) => resp.json())
            .then(function(json) {
                json.forEach(function(obj) { 
                    replytext += "1. [" + obj.title + " (" + obj.season + " Week " + obj.week + ")](https://gfycat.com/" + obj.gfycat + ") \n"; 
                });
            })
            .then(function(){
                console.log(replytext);
                if (replytext) {
                    comment.reply(replytext);    
                }
                else {
                    comment.reply("Sorry, there's no highlights for those terms.");
                    console.log("Sorry, there's no highlights for those terms.");
                }
            })
            .catch(function(error) {
                replytext += 'Whoops, something went wrong!';
            });
    }
});