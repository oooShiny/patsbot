require('dotenv').config({ path: '../.env' });
let express = require('express');
let path = require('path');
let bodyParser = require('body-parser');

let Pusher = require('pusher');

let pusher = new Pusher({
    appId: process.env.PUSHER_ID,
    key: process.env.PUSHER_KEY,
    secret: process.env.PUSHER_SECRET,
    cluster: 'us2',
    useTLS: true
});

let app = express();

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: false }));
app.use(express.static(path.join(__dirname, 'public')));

app.post('/comment', function(req, res) {
    let newComment = {
        name: req.body.name,
        comment: req.body.comment
    };
    let event = 'play-chat-' + req.body.play_id;
    pusher.trigger('sp0rts-comments', event, newComment);
    res.json({ created: true });
    console.log('Comment Posted:');
    console.log(req.body);
});

// Error Handler for 404 Pages
app.use(function(req, res, next) {
    let error404 = new Error('Route Not Found');
    error404.status = 404;
    next(error404);
});

module.exports = app;

app.listen(9000, function() {
    console.log('Example app listening on port 9000!');
});
