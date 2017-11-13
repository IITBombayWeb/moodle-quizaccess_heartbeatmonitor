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
	host : "localhost",
	user : "root",
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

var record = io.sockets.on('connection', function (socket) {	

	count++;
	sockarr[count] = socket;
	console.log(count + ' - ' + socket.id + ' socket connected');
	
	console.log('count: ' + io.sockets.server.eio.clientsCount);
	//============================================================
	socket.on('attempt', function(data) {
		console.log('In attempt event');
				
		// Construct record for socket connected.
	    socket.username 		= data.username;
	    socket.quizid 			= data.quizid;
	    socket.roomid 			= data.roomid;
	    socket.socketid 		= socket.id;
	    socket.statusConnected	= 'Connected';
	    socket.timestampC		= socket.handshake.issued;
	    socket.ip				= socket.request.connection.remoteAddress;

	    var socketobject = {  username : socket.username, 
	    						quizid : socket.quizid, 
	    						roomid : socket.roomid, 
	    					  socketid : socket.id, 
	    						    ip : socket.ip
	    					};
	    console.log('Socket object - ' + socketobject.username);
	    
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
	    	console.log('Connected sockets - ' + connectedSockets[i]);
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
	    	console.log('Record for connected socket : ' + socket.socketid + ' is inserted into the db');
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
		
		//=====================================================================
		// Construct record for socket disconnected.
		socket.timestampD = new Date().getTime();
		socket.statusDisconnected = 'Disconnected';
	    
	    // Add to the deletedSockets array.
	    deletedSockets.push(socket.id);

	    // Find the index of the object having socket id which got disconnected.
	    var index = connectedSockets.findIndex( function(o) {
	        return o.id === socket.id;
	    });
	    
	    // Remove the disconnected socket object from the connectedSockets array.
	    if (index !== -1) connectedSockets.splice(index, 1);
	    console.log('Disconnected sockets - ' + deletedSockets);
	    
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
	    	console.log('Record for disconnected socket : ' + socket.socketid + ' is inserted into db');
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
	          "group by username, quizid, roomid";
	con.query(sql, function(err, result,fields) {
		if (err) throw err;
		res.send(result);
	});
});

http.listen(port, function() {
	console.log('Listening on port ' + port);
});