require('dotenv').config({ path: '../.env' });

const Snoowrap = require('snoowrap');
const { CommentStream, SubmissionStream } = require('snoostorm');

// Create a place to store the content.
const store = require('data-store')({ path: process.cwd() + '/gifdata.json' });

// Build Snoowrap and Snoostorm clients
const r = new Snoowrap({
    userAgent: 'patsbot',
    clientId: process.env.CLIENT_ID,
    clientSecret: process.env.CLIENT_SECRET,
    username: process.env.REDDIT_USER,
    password: process.env.REDDIT_PASS
});

// Configure options for stream: subreddit & results per query
const nflStreamOpts = {
    subreddit: 'nflgifbot+dyj+patriots',
    limit: 25,
    pollTime: 2000,
};

// Create a Snoostorm CommentStream with the specified options
const commentStream = new CommentStream(r, nflStreamOpts);

// Look for Timnog gif link comments.
commentStream.on('item', (comment) => {
    // Get comments from gif posters that have a link in 'em.
    if ((comment.author.name == 'arbrown83' || comment.author.name == 'timnog') &&
        comment.body_html.includes('href')) {
        // If the link is from a gif site, save it.
        if (comment.body_html.includes('gfycat') || comment.body_html.includes('streamable')) {
            let today = new Date();
            let dd = String(today.getDate()).padStart(2, '0');
            let mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0!
            let yyyy = today.getFullYear();
            today = yyyy + mm + dd;
            // Save to JSON file.
            let lines = comment.body.split(/\r?\n/);
            if (lines.length > 1) {
                for (let i = 0, l = lines.length; i < l; i++) {
                    if (lines[i].length > 0 && /^-?\d+$/.test(lines[i].charAt(0))) {
                        store.union(today, lines[i]);
                    }
                }
            } else {
                store.union(today, comment.body);
            }

            console.log(store);
        }
    }
});

// Create a Snoostorm CommentStream with the specified options
const postStream = new SubmissionStream(r, nflStreamOpts);

// Look for Game Thread posts from the other bot.
postStream.on('item', (post) => {
    if ((post.author.name == 'arbrown83' || post.author.name == 'patsbot') &&
        post.title.includes('Official')) {
        post.sticky();
        post.setSuggestedSort('new');
    }
});
