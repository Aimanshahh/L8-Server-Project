var
    env = require('dotenv').config({path: '../.env'}),
    redis_host = process.env.REDIS_HOST ? process.env.REDIS_HOST : '127.0.0.1',
    redis_port = process.env.REDIS_PORT ? process.env.REDIS_PORT : 6379,
    redis_db = process.env.REDIS_DB ? process.env.REDIS_DB : 1,
    http_port = process.env.SOCKET_PORT ? process.env.SOCKET_PORT : 9001,
    https_port = process.env.SOCKET_SSL_PORT ? process.env.SOCKET_SSL_PORT : 9002;

var
    fs = require('fs'),
    http = require('http'),
    https = require('https'),
    ioServer  = require('socket.io'),
    io = new ioServer(),
    redis = require('ioredis')({
        host: redis_host,
        port: redis_port,
        db: redis_db,
        password: process.env.REDIS_PASSWORD || undefined
    }),
    httpServer,
    httpsServer;

try {
    httpServer = http.createServer();
    httpServer.listen(http_port);
    io.attach( httpServer );
}
catch(err) {
    //console.log(err.message);
}

try {
    httpsServer = https.createServer({
        key: fs.readFileSync(process.env.SOCKET_SSL_KEY || '/var/www/html/private.key'),
        cert: fs.readFileSync(process.env.SOCKET_SSL_CERT || '/var/www/html/cert.crt'),
        ca: fs.readFileSync(process.env.SOCKET_SSL_CA || '/var/www/html/cert_ca.crt')
    });
    httpsServer.listen(https_port);
    io.attach( httpsServer );
}
catch(err) {
    console.log(err.message);
}

io.sockets.on('connection', function(socket) {
    //console.log('connection', socket.id);
    socket.on('join', function(room) {
        //console.log('room', room);
        socket.join(room);
    });
});

redis.psubscribe('*', function(err, count) { });

redis.on('pmessage', function(subscribed, channel, message) {
    //console.log('pmessage', subscribed, channel, message);
    message = JSON.parse(message);
    io.sockets.in(channel).emit(message.event, message.data);
});
