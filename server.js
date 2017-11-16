// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Server script for quizaccess_heartbeatmonitor plugin.
 *
 * @package    quizaccess
 * @subpackage heartbeatmonitor
 * @author     P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var app 		= require('express')();
var http 		= require('http').createServer(app);
var io			= require('socket.io').listen(http);
var util 		= require('util');
var CircularJSON	= require('circular-json');

var bodyParser 	= require('body-parser');
var mysql 		= require('mysql');

var con = mysql.createConnection({
	host 	 : "localhost",
	user 	 : "root",
	password : "root123",
	database : "trialdb"
});

app.use(bodyParser.urlencoded({ extended: true }));

console.log('In server.js file');

var port = 3000;
var count = 0;
var sockarr = [];

//=================================================================================
app.get('/admin-socketinfo', function (req, res) {
	res.sendFile(__dirname + '/admin-socketinfo.html');
});

app.get('/admin-userstatus', function (req, res) {
	res.sendFile(__dirname + '/admin-userstatus.html');
});

// Send connectedsockets array to the admin-socketinfo page.
var connectedSockets = [];
var connsckts; 

app.get('/connectedsocketids', function(req, res) {
//	console.log('-------------cir json obj------------------');
//	console.log(connsckts);
	res.send(connsckts);
})

// Send deletedsockets array to the admin-socketinfo page.
var deletedSockets = [];
app.get('/deletedsocketids', function(req, res) {
	res.send(deletedSockets);
})
//=================================================================================

var connow, prevdiscon, starttime, endtime, whattime;
var slot = 0;

var record = io.sockets.on('connection', function (socket) {	
	
	count++;
	sockarr[count] = socket;
	console.log(count + ' - ' + socket.id + ' socket connected');
	
//	console.log('total count: ' + io.sockets.server.eio.clientsCount);
	
	//============================================================
	socket.on('attempt', function(data) {
//		console.log('In attempt event');
				
		// Construct record for socket connected.
	    socket.username 		= data.username;
	    socket.quizid 			= data.quizid;
	    socket.roomid 			= data.roomid;
	    socket.socketid 		= socket.id;
	    socket.statusConnected	= 'Connected';
	    socket.timestampC		= socket.handshake.issued;
	    socket.ip				= socket.request.connection.remoteAddress;
	    
	    connow = socket.timestampC;
	    if(starttime !== undefined) {
	    	if((connow - prevdiscon) > 2000) {
		    	starttime = socket.timestampC;
		    	console.log('starttime: ' + starttime);
		    }
	    } else {
	    	var maxts, username, quizid, roomid, ip;
	    	starttime = socket.timestampC;
	    	console.log('starttime: ' + starttime);
	    	console.log('prevdiscon: ' + prevdiscon);
	    	
	    	var sql1 = "select username, quizid, roomid, " + 
							"max(case when socketstatus   = 'Disconnected' 	then timestamp end) as max_disconnected_timestamp " + 
							"from socketinfo " + 
							"where username = '" + socket.username + "' AND " + 
							"quizid = " + socket.quizid + " AND " + 
							"roomid = '" + socket.roomid + "' " + 
							"group by username, quizid, roomid";
							
	    	
	    	con.query(sql1, function (err, result) {
		    	if (err) throw err;
		    	console.log(result);
		    	for (var i in result) {
		    		username = result[i].username;
		    		quizid = result[i].quizid;
		    		roomid = result[i].roomid;
//		    		ip = result[i].ip;
					maxts = result[i].max_disconnected_timestamp;
			    	console.log('maxts: ' + maxts);
		    	}
		    });
	    	
//	    	Query socketinfo2 for getting slot and ip
//	    	var sql1 = "select username, quizid, roomid, " + 
//			"max(case when socketstatus   = 'Disconnected' 	then timestamp end) as max_disconnected_timestamp " + 
//			"from socketinfo " + 
//			"where username = '" + socket.username + "' AND " + 
//			"quizid = " + socket.quizid + " AND " + 
//			"roomid = '" + socket.roomid + "' " + 
//			"group by username, quizid, roomid";
//			
//
//			con.query(sql1, function (err, result) {
//				if (err) throw err;
//				console.log(result);
//				for (var i in result) {
//					username = result[i].username;
//					quizid = result[i].quizid;
//					roomid = result[i].roomid;
//				//	ip = result[i].ip;
//					maxts = result[i].max_disconnected_timestamp;
//					console.log('maxts: ' + maxts);
//				}
//			});
	    	
	    	var sql2 = "INSERT INTO socketinfo2 (username, quizid, roomid, slot, whattime, ip, timestamp) VALUES" 
							+ "('" + username + "', " 
							+ quizid + ", '" 
							+ roomid + "', '" 
							+ "1" + "', '"
							+ "endtime" + "', '" 
							+ "null" + "', " 
							+ maxts + ")";
			con.query(sql2, function (err, result) {
				if (err) throw err;
			//	console.log('Record for connected socket : ' + socket.socketid + ' is inserted into the db');
			});
	    	
	    	// Insert connection record into database.
	    	slot++;
	    	whattime = 'starttime';
		    var sql = "INSERT INTO socketinfo2 (username, quizid, roomid, slot, whattime, ip, timestamp) VALUES" 
		    				+ "('" + socket.username + "', " 
		    				+ socket.quizid + ", '" 
		    				+ socket.roomid + "', '" 
		    				+ slot + "', '"
		    				+ whattime + "', '" 
		    				+ socket.ip + "', " 
		    				+ socket.timestampC + ")";
		    con.query(sql, function (err, result) {
		    	if (err) throw err;
//		    	console.log('Record for connected socket : ' + socket.socketid + ' is inserted into the db');
		    });
	    	
	    }
	    	    
	    
	    // Rooms code start============================================================
	    socket.join(data.roomid);
//	    console.log("======/ Rooms Connect=========");
	    var clients = io.sockets.adapter.rooms[data.roomid].sockets;   

	    //to get the number of clients
	    var numClients = (typeof clients !== 'undefined') ? Object.keys(clients).length : 0;
	    console.log(data.roomid + ' room count: ' + numClients);
//	    if(numClients == 1) {
//	    	// Insert into db
//	    }
	    var s = 0;
	    for (var clientId in clients) {
	    	//this is the socket of each client in the room.
	    	s++;
	    	var clientSocket = io.sockets.connected[clientId];
//	    	console.log('S' + s + ': ' + clientSocket.id + ' TS: ' + socket.timestampC);
	    }
//	    console.log("======Rooms Connect /=========");
	    // Rooms code end============================================================

	    var socketobject = {  username : socket.username, 
	    						quizid : socket.quizid, 
	    						roomid : socket.roomid, 
	    					  socketid : socket.id, 
	    						    ip : socket.ip
	    					};
//	    console.log('Socket object - ' + socketobject.username);
	    
	    // Add to the connectedSockets array.
//	    connectedSockets.push(socketobject);
//	    console.log(io.sockets.sockets);
	    
	    // To convert obj with cir. ref. into JSON 
	    // 1 - Using util ============================================
//	    convertedobj = util.inspect(io.sockets.sockets);
//	    connsckts = JSON.stringify(convertedobj); 
		
	    // 2 - Using CircularJSON ============================================
	    connsckts = CircularJSON.stringify(io.sockets.connected);
	    
	    for(i=0; i<connectedSockets.length; i++) {
//	    	console.log('Connected sockets - ' + connectedSockets[i]);
	    }
	    
	    // Insert connection record into database.
	    var sql = "INSERT INTO socketinfo (username, quizid, roomid, socketid, socketstatus, ip, timestamp) VALUES" 
	    				+ "('" + socket.username + "', " 
	    				+ socket.quizid + ", '" 
	    				+ socket.roomid + "', '" 
	    				+ socket.socketid + "', '"
	    				+ socket.statusConnected + "', '" 
	    				+ socket.ip + "', " 
	    				+ socket.timestampC + ")";
	    con.query(sql, function (err, result) {
	    	if (err) throw err;
//	    	console.log('Record for connected socket : ' + socket.socketid + ' is inserted into the db');
	    });
	});
	//============================================================
	
	socket.on('disconnect', function() {
		
		for (const [key, value] of Object.entries(sockarr)) {
			if (value === socket) {
				var index = key;
			}
		}
		console.log(index + ' - ' + socket.id + ' socket disconnected');
//		console.log('count: ' + io.sockets.server.eio.clientsCount);
		
		//=====================================================================
		// Construct record for socket disconnected.
		socket.timestampD = new Date().getTime();
		socket.statusDisconnected = 'Disconnected';
		prevdiscon = socket.timestampD;
//		console.log(socket);
		//=========================My logic============================================
//	    console.log("=========/ Rooms Disconnect========");
//	    console.log('S: ' + socket.id + ' TS: ' + socket.timestampD);
	    var rooms = io.sockets.adapter.rooms[socket.roomid]; 
//	    console.log(rooms);

	    //to get the number of clients
//	    var numClients = (typeof clients !== 'undefined') ? Object.keys(clients).length : 0;
	    if(typeof rooms !== 'undefined') {
	    	var clients = io.sockets.adapter.rooms[socket.roomid].sockets;
	    	var numClients = Object.keys(clients).length;
	    	var s = 0;
		    for (var clientId in clients) {
		    	//this is the socket of each client in the room.
		    	s++;
		    	var clientSocket = io.sockets.connected[clientId];
//		    	console.log('S' + s + ': ' + clientSocket.id + ' TS: ' + socket.timestampD);
		    }
	    } else {
	    	var numClients = 0;
	    }
//	    console.log(socket.roomid + ' room count: ' + numClients);
	    if(numClients == 0) {
	    	// Insert into db
	    }
	    
//	    console.log("=========Rooms Disconnect /========");
	    //=============================================================================
	    
	    // Add to the deletedSockets array.
	    deletedSockets.push(socket.id);

	    // Find the index of the object having socket id which got disconnected.
	    var index = connectedSockets.findIndex( function(o) {
	        return o.id === socket.id;
	    });
	    
	    // Remove the disconnected socket object from the connectedSockets array.
	    if (index !== -1) connectedSockets.splice(index, 1);
//	    console.log('Disconnected sockets - ' + deletedSockets);
	    
	    // Insert disconnection record into database.
	    var sql = "INSERT INTO socketinfo (username, quizid, roomid, socketid, socketstatus, ip, timestamp) VALUES" 
						+ "('" + socket.username + "', " 
						+ socket.quizid + ", '" 
						+ socket.roomid + "', '" 
						+ socket.socketid + "', '"
						+ socket.statusDisconnected + "', '" 
						+ socket.ip + "', " 
						+ socket.timestampD + ")";

	    con.query(sql, function(err, result) {
	    	if (err) throw err;
//	    	console.log('Record for disconnected socket : ' + socket.socketid + ' is inserted into db');
	    });
	    
	    
	    //=====================================================================
	});
});

app.get('/livestatus', function(req, res) {
	var sql = "select username, quizid, roomid, " + 
					"count(case when socketstatus = 'Connected' 	then socketid end) 	as count_connected_socketid, " + 
					"count(case when socketstatus = 'Disconnected' 	then socketid end) 	as count_disconnected_socketid, " + 
					"max(case when socketstatus   = 'Connected' 	then timestamp end) as max_connected_timestamp, " + 
					"max(case when socketstatus   = 'Disconnected' 	then timestamp end) as max_disconnected_timestamp " + 
	          "from socketinfo " + 
	          "group by username, quizid, roomid";// + 
//	          "where ";
	
//	var sql1 = "select username, quizid, roomid, timestamp from ( select * from socketinfo order by timestamp desc) " + 
//					"where rownum<=" + io.sockets.server.eio.clientsCount  + "";
	
	var roomids = "Select DISTINCT roomid from socketinfo";
	
	con.query(sql, function(err, result, fields) {
		if (err) throw err;
//		console.log(result);
		res.send(result);
	});
	
//	con.query(roomids, function(err, result, fields) {
//		if (err) throw err;
////		console.log(result[0].roomid);
////		res.send(result);
//		
//		for (var i in result) {
//			console.log("=========livestatus========");
//			console.log(result[i].roomid);
//			roomid = result[i].roomid;
//						
////			if() {				  
//				//to get the number of clients
//			
//				var rooms = io.sockets.adapter.rooms[roomid];
////			    var numClients = (typeof clients !== 'undefined') ? Object.keys(clients).length : 0;
//			    if(typeof rooms !== 'undefined') {
//			    	var clients = io.sockets.adapter.rooms[socket.roomid].sockets;
//			    	var numClients = Object.keys(clients).length;
//			    	for (var clientId in clients) {
//					       //this is the socket of each client in the room.
//					       var clientSocket = io.sockets.connected[clientId];
//					       console.log(clientSocket.id);
//					}
//			    } else {
//			    	var numClients = 0;
//			    }
//			    console.log(numClients);			    
//			    
////			}
//		}
//	});
});

http.listen(port, function() {
	console.log('Listening on port ' + port);
});