require('dotenv').config();

const Snoowrap = require('snoowrap');
const { CommentStream } = require("snoostorm");

// Create a place to store the content.
const store = require('data-store')({ path: process.cwd() + '/gifdata.json' });

// Build Snoowrap and Snoostorm clients
const r = new Snoowrap({
    userAgent: 'reddit-bot-example-node',
    clientId: process.env.CLIENT_ID,
    clientSecret: process.env.CLIENT_SECRET,
    username: process.env.REDDIT_USER,
    password: process.env.REDDIT_PASS
});

// Configure options for stream: subreddit & results per query
const nflStreamOpts = {
    subreddit: 'nflgifbot+dyj',
    results: 40
};

// Create a Snoostorm CommentStream with the specified options
const comments = new CommentStream(nflStreamOpts); 

// Look for Timnog gif link comments.
comments.on('comment', (comment) => {
    // Get comments from gif posters that have a link in 'em.
	if ((comment.author.name == 'arbrown83' || comment.author.name == 'timnog') && comment.body_html.includes('href')) {
        // If the link is from a gif site, save it.
        if (comment.body_html.includes('gfycat') || comment.body_html.includes('streamable')) {
            console.log(comment.body);
            var today = new Date();
            var dd = String(today.getDate()).padStart(2, '0');
            var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
            var yyyy = today.getFullYear();
            today = yyyy + mm + dd;
            // Save to JSON file.
            store.union(today, comment.body);
            console.log(store)
        }
    }
});