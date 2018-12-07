/** Room(name, id[, host])
 *  Represent a room
 *
 *  Properties:
 *      .name == room name
 *      .id   == room id
 *      .log  == list of chat history of Msg object after login
 *      .unread == number of message not being read
 *      .userlist == dict of user in that room, true means this user is host.
 *
 *  Functions:
 *      .addLog(msg, show)  add a Msg object to chat history, when show is true, showLog(msg)
 *      .showAll() display all the chat history and room name
 *      .showLog(msg) display Msg object
 */
function Room(name, id, host) {
	this.name = name;
    this.id = id;
    this.log = [];
    this.unread = 0;
    this.userlist = {};
	this.host = host;
    this.userlist[host] = true;
    
    this.addUser = function(username){
        if(!(username in this.userlist)){
            this.userlist[username] = false;
        }
    };
    
    this.delUser = function(username){
        delete this.userlist[username];
    };
    
    this.showUser = function(ishost){
        $('#cur-room-user').text('');
        for (let user in this.userlist) {
			let parent = document.createElement('div');
			$(parent).attr('class','d-flex user-btn-group');
            let node1 = document.createElement('button');
            $(node1).attr('class', "d-flex flex-shrink-0 flex-grow-1 btn justify-content-between bg-user text-gray onhover-text p-0 user-btn");
            $(node1).attr('type', 'button');
            let node2 = document.createElement('div');
            $(node2).attr('class', 'my-auto');
            $(node2).text(user);
            $(node1).append(node2);
			$(parent).append(node1);
			if(ishost && user !== this.host){
				$(parent).append(
				`<button type="button" class="btn-kick d-flex flex-shrink-0 btn justify-content-between bg-user text-gray onhover-text onhover-btn bg-btn-kick ml-1 p-0 no-display">
					<div class="my-auto px-1">kick</div>
				</button>
				<button type="button" class="btn-ban d-flex flex-shrink-0 btn justify-content-between bg-user text-gray onhover-text onhover-btn bg-btn-kick ml-1 p-0 no-display">
					<div class="my-auto px-1">ban</div>
				</button>`);
			}
			$("#cur-room-user").append(parent);
        }
		$('.user-btn-group').hover(function(){
			$(this).find('.bg-btn-kick').removeClass('no-display');
		},function(){
			$(this).find('.bg-btn-kick').addClass('no-display');
		});
		$('.btn-kick').click(function(){
			let send = {};
			send.room = rooms[curroom.label].name;
			send.user = $(this).parent().children(':first').children(':first').text();
			socket.emit('kick user', JSON.stringify(send));
		});
		$('.btn-ban').click(function(){
			let send = {};
			send.room = rooms[curroom.label].name;
			send.user = $(this).parent().children(':first').children(':first').text();
			socket.emit('ban user', JSON.stringify(send));
		});
        $('.user-btn').click(function(){
			if(self.name === $(this).children(':first').text()){
				return;
			}
			curroom.ispm = true;
			curroom.label = $(this).children(':first').text();
			let pm = new PM(curroom.label);
			pm.showAll();
        });
    };
    
    this.addLog = function(msg, show){
        this.log.push(msg);
        this.unread++;
        if(show){
            this.showLog(msg);
        }
    };
    
    this.showAll = function(ishost){
		$('#hide-1').removeClass('no-display');
        $('#hide-2').removeClass('no-display');
        $('#cur-room-name').text(this.name);
        $('#dialog').text('');
        for(let i in this.log){
            $('#dialog').append(this.log[i].genNode());
        }
        this.unread = 0;
        this.showUser(ishost);
    };
    
    this.showLog = function(msg){
        $('#dialog').append(msg.genNode());
        this.unread = 0;
    };
}

function PM(user) {
	this.user = user;
	this.log = [];
	this.unread = 0;
	
	this.addLog = function(msg, show){
		this.log.push(msg);
        this.unread++;
        if(show){
            this.showLog(msg);
        }	
	};
	
	this.showAll = function(){
		$('#hide-1').removeClass('no-display');
        $('#hide-2').addClass('no-display');
        $('#cur-room-name').text(this.user);
        $('#dialog').text('');
        for(let i in this.log){
            $('#dialog').append(this.log[i].genNode());
        }
        this.unread = 0;
    };
	
	this.showLog = function(msg){
        $('#dialog').append(msg.genNode());
        this.unread = 0;
    };
}
/** Msg
 *  represent a message
 *
 *  Properties:
 *      .user == username
 *      .date == Date object of sending time of this message
 *      .isimg == true when msg is the src of img
 *      .msg  == message content
 *
 *  Functions:
 *      .genNode()  create DOM node
 */
function Msg(user, date, msg, isimg = false) {
	this.user = user;
    this.date = date;
    this.msg = msg;
	this.isimg = isimg;
    
    this.genNode = function(){
		let node = document.createElement("div");
		$(node).attr('class', "pb-2 border border-top-0 border-left-0 border-right-0 border-gray");
		let head = document.createElement("h6");
		$(head).attr('class', "mt-1");
		$(head).text(this.user+' ');
		$(head).append($(`<span class='smaller'>${this.date.toLocaleTimeString()} ${this.date.toLocaleDateString()}</span>`));
		$(node).append(head);
		if(!this.isimg){
			let body = document.createElement('p');
			$(body).attr('class', 'mb-1 word-wrap');
			$(body).text(this.msg);
			$(node).append(body);
		}else{
			let body = $(`<div class="w-50">
                                <img src="client.jpg" alt="test" class="img-fluid mb-1">
                       </div>`);
			$(body).children(':first').attr('src', this.msg);
			$(node).append(body);
		}
        return node;
    };
}


function updateRoomlist() {
    $('#room-list').text(''); 
    for(let key in rooms){
        let node = document.createElement('button');
        $(node).attr('class', "d-flex flex-shrink-0 btn bg-room text-gray onhover-text justify-content-between p-0 room-btn");
        $(node).attr('type', 'button');
        $(node).attr('roomid', rooms[key].id);
        let roomname = document.createElement('div');
        $(roomname).attr('class', 'my-auto ellipsis');
        $(roomname).text(rooms[key].name);
        $(node).append(roomname);
        $(node).append(`<div class="my-auto badge badge-light">${rooms[key].unread}</div>`);
        $('#room-list').append(node);
    }
    $(".room-btn").click(function(){
		curroom.ispm = false;
        curroom.label = parseInt($(this).attr('roomid'));
        rooms[curroom.label].showAll(rooms[curroom.label].userlist[self.name]);
		$("#dialog").scrollTop(Number.MAX_SAFE_INTEGER);
		updateRoomlist();
    });
}

function updatePMlist() {
    $('#pm-list').text(''); 
    for(let key in pms){
        let node = document.createElement('button');
        $(node).attr('class', "d-flex flex-shrink-0 btn bg-room text-gray onhover-text justify-content-between p-0 pm-btn");
        $(node).attr('type', 'button');
        let username = document.createElement('div');
        $(username).attr('class', 'my-auto ellipsis');
        $(username).text(pms[key].user);
        $(node).append(username);
        $(node).append(`<div class="my-auto badge badge-light">${pms[key].unread}</div>`);
        $('#pm-list').append(node);
    }
    $(".pm-btn").click(function(){
		curroom.ispm = true;
        curroom.label = $(this).children(':first').text();
        pms[curroom.label].showAll();
		$("#dialog").scrollTop(Number.MAX_SAFE_INTEGER);
		updatePMlist();
    });
}

function emoji() {
	
}
let rooms = {};
let pms = {};
let self = {};
let socket = io();
let uploader = new SocketIOFileUpload(socket);

// current selected room
let curroom = {
	ispm: false,  
	label: -1 // room id or username
};
const regex = {
	'user': new RegExp(/^[a-zA-Z0-9]+([._]?[a-zA-Z0-9]+)*$/),
	'room': new RegExp(/\S+/)
};

$(document).ready(function(){
	$.getJSON( "emoji", function(data) {
		
		let list = data.list;
		let grid = $(`<div class="d-flex flex-column h-25"></div>`);
		for(let i=0;i<10;i++){
			let row = $(`<div class="d-flex"></div>`);
			for(let j=0;j<10;j++){
				let col = $(`<div class="d-flex mr-1 emoji">${String.fromCodePoint('0x'+list[10*i+j])}</div>`);
				$(row).append(col);
			}
			$(grid).append(row);
		}
		$('#emoji-btn').popover({
			title:'',
			//https://stackoverflow.com/questions/5744207/jquery-outer-html
			content:$('<div>').append($(grid).clone()).html(),
			html:true,
			placement:'top'
		});
		$('#emoji-btn').click(function(){
			$('.emoji').click(function(){
				$('#msg').val($('#msg').val()+$(this).text());
			});
		});
	});
    $('#login-modal').modal({backdrop:'static',keyboard:false, show:true});
    $('#login-btn').click(function(){
		if(regex.user.test($('#login-user').val())){
			$(this).attr('disabled','disabled');
			socket.emit('new user',$('#login-user').val());
		}else{
			$('#login-helper').text("You could choose from letters, underscore and dot. Underscore and dot can't be at the end or the start, or next to each other, or used multiple times in a row");
			$('#login-helper').removeClass('no-display');
		}
    });
    // Click btn when enter key pressed
    // https://stackoverflow.com/a/7937462
    $('#login-user').keypress(function(event){
        if(event.keyCode == 13){
            $('#login-btn').click();
        }
    });
    $('#to-join').click(function(){
        $('#join-modal').modal('show');
    });
    $('#to-create').click(function(){
        $('#create-modal').modal('show');
    });
	$('input[name=room-attr]').change(function(){
		if($('input[name=room-attr]:checked').val() == 'private'){
			$('#create-pwd').removeClass('no-display');
		}else{
			$('#create-pwd').addClass('no-display');
		}
	});
	$('#create-btn').click(function(){
		let send = {};
		send.attr = $('input[name=room-attr]:checked').val();
		send.pwd = '';
		if(send.attr === 'private'){
			send.pwd = $('#create-pwd').val();
			if(!regex.room.test(send.pwd)){
				alert('please input password.');
				return;
			}
		}
		send.room = $('#create-room').val();
		if(!regex.room.test(send.room)){
			alert('please input room name.');
		}
		socket.emit('new room', JSON.stringify(send));
	});
	$('#create-room').keypress(function(event){
        if(event.keyCode == 13){
            $('#create-btn').click();
        }
    });
	$('#create-pwd').keypress(function(event){
        if(event.keyCode == 13){
            $('#create-btn').click();
        }
    });
	$('#join-btn').click(function(){
		let send = {};
		send.room = $('#join-room').val();
		send.pwd = $('#join-pwd').val();
		if(!regex.room.test(send.room)){
			alert('please input room name.');
		}
		socket.emit('join room', JSON.stringify(send));
	});
	$('#join-room').keypress(function(event){
        if(event.keyCode == 13){
            $('#join-btn').click();
        }
    });
	$('#join-pwd').keypress(function(event){
        if(event.keyCode == 13){
            $('#join-btn').click();
        }
    });
	$('#msg').keypress(function(event){
		if(event.keyCode != 13){
			return;
		}
		let send = {};
		if(!curroom.ispm){
			send.room = rooms[curroom.label].name;
			send.msg = $("#msg").val();
			if(send.msg === ''){
				return;
			}
			socket.emit('room msg', JSON.stringify(send));
			$("#msg").val('');
		}else{
			send.to = curroom.label;
			send.msg = $('#msg').val();
			if(send.msg === ''){
				return;
			}
			if(!(send.to in pms)){
				pms[send.to] = new PM(send.to);
			}
			pms[send.to].addLog(new Msg(self.name, new Date(), send.msg), true);
			updatePMlist();
			socket.emit('private msg', JSON.stringify(send));
			$("#msg").val('');
		}
	});
	$('#img-upload').click(function(){
		$('#img-input').click();
	});
	$('#img-input').change(function(){
		if($(this).prop('files') && $(this).prop('files')[0] && $(this).prop('files')[0].type.includes('image')){
			uploader.submitFiles($(this).prop('files'));
		}
	});
	uploader.addEventListener("start",function(event){
		event.file.meta.ispm = curroom.ispm;
		if(!curroom.ispm){
			event.file.meta.to = rooms[curroom.label].name;
		}else{
			event.file.meta.to = curroom.label;
		}
	});
	uploader.addEventListener("complete",function(event){
		if(event.detail.ispm){
			let send = {};
			send.to = curroom.label;
			send.msg = event.detail.path;
			if(!(send.to in pms)){
				pms[send.to] = new PM(send.to);
			}
			pms[send.to].addLog(new Msg(self.name, new Date(), send.msg, true), true);
			updatePMlist();
		}
	});
	
    /**JSON response
     * res.action == 0(login)
     *    .status == true when success
     *    .id == socket id
     *    -----------------------------------------
     *    .action == 1(newroom: expect to receive after create room)
     *    .status == true when success 
     *    .err == (when false) room name exists, room name not valid
     *    .room.id == room id
     *         .name == room name
     *         .host == host
     *         .userlist == [list of users]
     *    -----------------------------------------
     *    .action == 2(joinroom: receive after join room)
     *    .status == true when success
     *    .pwd == true when status false and require pwd
     *    .err == room name not exists, wrong password   
     *    .room.id == room id
     *         .name == room name
     *         .host == host
     *         .userlist == [list of users]
     *    -----------------------------------------
     *    .action == 3(delroom) (room will be deleted when host leave)
     *    .roomid == room id
     *    .info  == host left or kick or ban
     *    -----------------------------------------
     *    .action == 4(newuser)
     *    .roomid == room id
     *    .username == user name
     *    -----------------------------------------
     *    .action == 5(deluser)
     *    .roomid == room id
     *    .username == user name
     *    -----------------------------------------    
     *    .action == 6(roommsg)
     *    .roomid == room id
     *    .msg.user == username
     *        .date == time
     *        .isimg == true when content is the src of img
     *        .content == content
     *    -----------------------------------------
     *    .action == 7(primsg) (private msg)
     *    .msg.from
     *        .date
     *        .isimg ==  true when content is the src of img
     *        .content
     *    -----------------------------------------
     *    .action == 8(delete pm)
     *    .user
     *    -----------------------------------------
     */
    socket.on('event', function(res){
        res = JSON.parse(res);
        switch(res.action){
            //login
            case 0:
                if(res.status){
                    self.name = $('#login-user').val();
                    self.id = res.id;
                    $('#login-modal').modal('hide');
                    $('#disp-user').text(self.name);
                    $('#login-helper').addClass('no-display');
                }else{
					$('#login-helper').text("Username exists");
                    $('#login-helper').removeClass('no-display');
                }
                $('#login-user').val('');
                $('#login-btn').removeAttr('disabled');
                break;
            // new room
            case 1:
                if(res.status){
					curroom.ispm = false;
                    curroom.label = res.room.id;
                    rooms[curroom.label]=new Room(res.room.name, res.room.id, res.room.host);
                    for(let i in res.userlist){
                        rooms[curroom.label].addUser(res.userlist[i]);
                    }
                    rooms[curroom.label].showAll(rooms[curroom.label].userlist[self.name]);
                    updateRoomlist();
					$('#create-modal').modal('hide');
                }else{
                    alert(res.err);
                }
				$('#create-room').val('');
				$('#radio0').click();
				$('#create-pwd').val('');
                break;
            // join room
            case 2:
                if(res.status){
					curroom.ispm = false;
                    curroom.label = res.room.id;
					if(!(curroom.label in rooms)){
						rooms[curroom.label]=new Room(res.room.name, res.room.id, res.room.host);
						for(let i in res.room.userlist){
							rooms[curroom.label].addUser(res.room.userlist[i]);
						}
					}   
                    $('#hide-1').removeClass('no-display');
                    $('#hide-2').removeClass('no-display');
					$('#join-pwd-show').addClass('no-display');
					$('#join-room').val('');
					$('#join-pwd-show').val('');
                    rooms[curroom.label].showAll(rooms[curroom.label].userlist[self.name]);
                    updateRoomlist();
					$('#join-modal').modal('hide');
                }else if(res.pwd){
                    $('#join-pwd-show').removeClass('no-display');
                }else{
					alert(res.err);
					$('#join-pwd-show').val('');
				}
                break;
            // del room
            case 3:
                delete rooms[res.roomid];
                if(!curroom.ispm && res.roomid == curroom.label){
                    alert(res.info);
                    $('#hide-1').addClass('no-display');
                    $('#hide-2').addClass('no-display');
                }
                updateRoomlist();
                break;
            // new user
            case 4:
                rooms[res.roomid].addUser(res.username);
                if(!curroom.ispm && res.roomid === curroom.label){
                    rooms[curroom.label].showUser(rooms[curroom.label].userlist[self.name]);   
                }
                break;
            // del user
            case 5:
                rooms[res.roomid].delUser(res.username);
                if(!curroom.ispm && res.roomid === curroom.label){
                    rooms[curroom.label].showUser(rooms[curroom.label].userlist[self.name]);   
                }
                break;
            // room msg
            case 6:
                rooms[res.roomid].addLog(new Msg(res.msg.user, new Date(res.msg.date), res.msg.content, res.msg.isimg), !curroom.ispm && res.roomid == curroom.label);
                updateRoomlist();
                break;
            // private msg
            case 7:
				if(!(res.msg.from in pms)){
					pms[res.msg.from] = new PM(res.msg.from);
				}
				pms[res.msg.from].addLog(new Msg(res.msg.from, new Date(res.msg.date), res.msg.content, res.msg.isimg), curroom.ispm && res.msg.from == curroom.label);
				updatePMlist();
                break;
			// delete private msg
			case 8:
				if(res.user in pms){
					delete pms[res.user];
					if(curroom.ispm && res.user == curroom.label){
						alert('user sign out');
						$('#hide-1').addClass('no-display');
						$('#hide-2').addClass('no-display');
					}
					updatePMlist();
				}
				break;
            
            default: 
                alert("unrecognized action");
        }
    });
});
$(function(){
    $("[data-toggle=popover]").popover({
        html : true,
        content: function() {
          var content = $(this).attr("data-popover-content");
          return $(content).children(".popover-body").html();
        }
    });
});