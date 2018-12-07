let http = require('http');
let url = require('url');
let path = require('path');
let socketio = require('socket.io');
let fs = require('fs');
let emoji = require('emoji.json');
let SocketIOFileUpload = require("socketio-file-upload");
let mime = require('mime');

//https://stackoverflow.com/a/1535650
let roomId = (function() {
        var id = 0; // This is the private persistent value
        // The outer function returns a nested function that has access
        // to the persistent value.  It is this nested function we're storing
        // in the variable uniqueID above.
        return function() { return id++; };  // Return and increment
})();
let imgId = (function() {
        var id = 0; // This is the private persistent value
        // The outer function returns a nested function that has access
        // to the persistent value.  It is this nested function we're storing
        // in the variable uniqueID above.
        return function() { return id++; };  // Return and increment
})();

function Room(name, host, pwd) {
	this.id = roomId();
    this.name = name;
    this.userlist = {};
	this.host = host;
	this.userlist[host] = true;
    this.banlist = [];
	this.pwd = pwd;
	this.isprivate = this.pwd !=='';
    
    this.addUser = function(user){
		if(this.banlist.includes(user)){
			return false;
		}
        if(!(user in this.userlist)){
			this.userlist[user] = false;
		}
		return true;
    };
    
    this.getUsers = function(){
        return Object.keys(this.userlist);
    };
    
    this.del = function(){
        
    };
    
    this.kick = function(user){
        if(user in this.userlist){
			delete this.userlist[user];
		}
    };
    
    this.ban = function(user){
        this.kick(user);
		this.banlist.push(user);
    };
    
}

function User(name, id) {
    this.name = name;
    this.id = id;
	this.roomlist = [];
	
	this.addRoom = function(room){
		if(!(room in this.roomlist)){
			this.roomlist.push(room);
		}
	};
}

function genEmojilist() {
	let json = {};
	json.list = [];
	for(let i=0;i<100;i++){
		json.list.push(emoji[i].codes);
	}
	return json;
}

let app = http.createServer(function(req, res){
    let pathname = url.parse(req.url).pathname;
    let filename = path.join(__dirname, 'source', pathname);
    if(pathname === '/'){
        fs.readFile(filename + 'chat.html', function(err, data){
            if(err) return res.writeHead(500);
            console.log('send chat.html');
            res.writeHead(200);
            res.end(data);
        });
    }
	else if (pathname === '/emoji'){
		res.writeHead(200,{'Content-Type': 'application/json'});
		res.end(JSON.stringify(genEmojilist()));
	}
    else if (pathname !== '/siofu/client.js'){
        fs.readFile(filename, function(err, data){
            if (err) {
                // File exists but is not readable (permissions issue?)
                res.writeHead(500, {
                    "Content-Type": "text/plain"
                });
                res.write("Internal server error: could not read file");
                res.end();
                return;
            }
			let mimetype = mime.getType(filename);
            res.writeHead(200, {
				'Content-Type': mimetype	
			});
			res.write(data);
            res.end();
            return;
        });    
    }
});
app.listen(3456, function(){
    console.log('listening on *:3456');
});
SocketIOFileUpload.listen(app);
let io = socketio.listen(app);
let users = {};
let rooms = {};
io.sockets.on("connection", function(socket){
	let uploader = new SocketIOFileUpload();
	uploader.dir = "source/images";
	uploader.listen(socket);
	let selfname = '';
	// Rename uploader file
	uploader.on("saved", function(event){
		let meta = event.file.meta;
		let id = imgId();
		let imgPath = path.parse(event.file.pathName);
		fs.rename(event.file.pathName, path.join(imgPath.dir, id + imgPath.ext), function(err) {
            if(err) console.log('ERROR: ' + err);
        });
		let pathName = path.join('images', id + imgPath.ext);
		if(meta.ispm){
			let res = {};
			res.action = 7;
			res.msg = {};
			res.msg.from = selfname;
			res.msg.date = new Date();
			res.msg.isimg = true;
			res.msg.content = pathName;
			io.to(users[meta.to].id).emit('event', JSON.stringify(res));
			event.file.clientDetail.ispm = true;
			event.file.clientDetail.path = res.msg.content;
		}else{
			let res = {};
			res.msg = {};
			res.action = 6;
			res.roomid = rooms[meta.to].id;
			res.msg.user = selfname;
			res.msg.date = new Date();
			res.msg.isimg = true;
			res.msg.content = pathName;
			io.in(meta.to).emit('event', JSON.stringify(res));
			event.file.clientDetail.ispm = false;
		}
	});
	// Register
    socket.on("new user",function(user){
		let res = {};
		console.log(user);
		res.action = 0;	
		if(!(user in users)){
			users[user]=new User(user, socket.id);
			res.status = true;
			res.id = socket.id;
			selfname = user;
		}else{
			console.log(user + ' exists');
			res.status = false;
		}
		socket.emit("event",JSON.stringify(res));
	});
	// Create new room
	socket.on("new room",function(data){
		data = JSON.parse(data);
		console.log(`new room: ${data.room}`);
		let res={};
		res.action = 1;
		res.room = {};
		if(!(data.room in rooms)){
			rooms[data.room] = new Room(data.room, selfname, data.pwd);
			socket.join(data.room);
			res.status = true;
			res.room.name = data.room;
			res.room.id = rooms[data.room].id;
			console.log(`Attr: ${rooms[data.room].isprivate}`);
			res.room.host = selfname;
			res.room.userlist = rooms[data.room].getUsers();
			console.log('success');
			users[selfname].addRoom(data.room);
		}else{
			console.log('Room exists');
			res.status = false;
			res.err = 'Room exists';
		}
		socket.emit("event", JSON.stringify(res));
	});
	//Join a room
	socket.on('join room', function(data){
		data = JSON.parse(data);
		console.log(`${selfname} join ${data.room}`);
		let res={};
		res.action = 2;
		res.room = {};
		if(!(data.room in rooms)){
			res.status = false;
			res.pwd = false;
			res.err = 'Room not exists';
			console.log('Room not exists');
		}
		else{
			if(rooms[data.room].isprivate && data.pwd === ''){
				res.status = false;
				res.pwd = true;
			}
			else if(rooms[data.room].isprivate && data.pwd !== rooms[data.room].pwd){
				res.status = false;
				res.pwd = false;
				res.err = 'Wrong password';
				console.log('Wrong password');
			}
			else{
				if(rooms[data.room].addUser(selfname)){
					socket.join(data.room);
					res.status = true;
					res.room.name = data.room;
					res.room.id = rooms[data.room].id;
					res.room.host = rooms[data.room].host;
					res.room.userlist = rooms[data.room].getUsers();
					console.log(res.room.userlist);
					users[selfname].addRoom(data.room);
				}else{
					res.status = false;
					res.pwd = false;
					res.err = "banned by host";
					console.log('failed: in banlist');
				}
			}
		}
		socket.emit("event", JSON.stringify(res));
		if(res.status){
			// notify all user in this room new user joint
			console.log(rooms[data.room].userlist);
			let res_all = {};
			res_all.action = 4;
			res_all.roomid = rooms[data.room].id;
			res_all.username = selfname;
			io.in(data.room).emit('event', JSON.stringify(res_all));
		}
	});
	// room message
	socket.on("room msg", function(data){
		data = JSON.parse(data);
		console.log(`${selfname} says ${data.msg} in ${data.room}`);
		let res = {};
		res.msg = {};
		res.action = 6;
		res.roomid = rooms[data.room].id;
		res.msg.user = selfname;
		res.msg.date = new Date();
		res.msg.isimg = false;
		res.msg.content = data.msg;
		io.in(data.room).emit('event', JSON.stringify(res));
	});
	// kick user
	socket.on("kick user", function(data){
		data = JSON.parse(data);
		console.log(`${selfname} kick ${data.user} from ${data.room}`);
		if(!rooms[data.room].userlist[selfname]) {
			console.log(`Failed: ${selfname} is not host`);
			return;
		}
		rooms[data.room].kick(data.user);
		io.sockets.connected[users[data.user].id].leave(data.room);
		let res_all = {};
		res_all.action = 5;
		res_all.roomid = rooms[data.room].id;
		res_all.username = data.user;
		io.in(data.room).emit('event', JSON.stringify(res_all));
		let res = {};
		res.action = 3;
		res.roomid = rooms[data.room].id;
		res.info = 'kicked by host';
		io.sockets.connected[users[data.user].id].emit('event', JSON.stringify(res));
	}); 
	// ban user
	socket.on("ban user", function(data){
		data = JSON.parse(data);
		console.log(`${selfname} ban ${data.user} from ${data.room}`);
		if(!rooms[data.room].userlist[selfname]) {
			console.log(`Failed: ${selfname} is not host`);
			return;
		}
		rooms[data.room].ban(data.user);
		let res_all = {};
		res_all.action = 5;
		res_all.roomid = rooms[data.room].id;
		res_all.username = data.user;
		io.in(data.room).emit('event', JSON.stringify(res_all));
		let res = {};
		res.action = 3;
		res.roomid = rooms[data.room].id;
		res.info = 'banned by host';
		io.sockets.connected[users[data.user].id].emit('event', JSON.stringify(res));
	});
	// private msg
	socket.on("private msg", function(data){
		data = JSON.parse(data);
		console.log(`${selfname} send msg to ${data.to}`);
		let res = {};
		res.action = 7;
		res.msg = {};
		res.msg.from = selfname;
		res.msg.isimg = false;
		res.msg.date = new Date();
		res.msg.content = data.msg;
		io.to(users[data.to].id).emit('event', JSON.stringify(res));
	});
    socket.on("disconnect", function(){
        console.log(`${selfname} ${socket.id} disconnected`);
		try{
			for(let i in users[selfname].roomlist){
				room = users[selfname].roomlist[i];
				let res_all = {};
				if(rooms[room].userlist[selfname]){
					// host leave, delete room, all users leave
					let res_all = {};
					res_all.action = 3;
					res_all.roomid = rooms[room].id;
					res_all.info = 'host has left room';
					io.in(room).emit('event', JSON.stringify(res_all));
					// https://github.com/socketio/socket.io/issues/3042
					io.of('/').in(room).clients((error, socketIds) => {
						if(error) throw error;
						socketIds.forEach(socketId => io.sockets.sockets[socketId].leave(room));
					});
					delete rooms[room];
				}
				else{
					res_all.action = 5;
					res_all.roomid = rooms[room].id;
					res_all.username = selfname;
					rooms[room].kick(selfname);
					io.in(room).emit('event', JSON.stringify(res_all));
				}
			}
			let res = {};
			res.action = 8;
			res.user = selfname;
			io.emit('event', JSON.stringify(res));
			delete users[selfname];
		}catch(err){
		}
		
    });
});