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
 * @author     Prof. P Sunthar, Amrata Ramchandani <ramchandani.amrata@gmail.com>, Kashmira Nagwekar
 * @copyright  2017 IIT Bombay, India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//var express   = require('express');
//var app       = express();
//var server    = app.listen(3033);
//var io        = require('socket.io').listen(server);

var app 		= require('express')();
var http 		= require('http').createServer(app);
var io			= require('socket.io').listen(http);
var bodyParser 	= require('body-parser');
var mysql 		= require('mysql');
//var fs 			= require('fs');
var obj;


// EXEC SYNC - WORKS
//var child = require('child_process').execSync('php -r \'define("CLI_SCRIPT", true); include("../../../../config.php"); print json_encode($CFG);\'');
var child = require('child_process').execSync('php -r \'define("CLI_SCRIPT", true); include("/var/www/html/exams/config.php"); print json_encode($CFG);\'');

//var child = require('child_process').execSync('php -r \'include("/var/www/html/exams/config.php"); print json_encode($CFG);\'');

obj = JSON.parse(child);

// DB CONN
var con = mysql.createConnection({
    host 	 : obj.dbhost,
    user 	 : obj.dbuser,
    password : obj.dbpass,
    database : obj.dbname
});

app.use(bodyParser.urlencoded({ extended: true }));

var port = 3000;
var totalconnectedsockets;
var roomwisesockets;
var roomwisesocketids = [];
var allsocketscount;
var allsocketids;
var cfg;
var timeserverid;

// Insert new record for node server.
var timeserversql = "INSERT INTO exm_quizaccess_hbmon_timeserver (timestarted, lastlivetime) VALUES" +
    "(" + Math.floor((new Date().getTime())/1000) + "," 
    + Math.floor((new Date().getTime())/1000) + ")";

con.query(timeserversql, function(err, result) {
    if (err) throw err;
    currenttimeserverid = result.insertId;
    //	console.log('-- here result insert id -- ' + result.insertId);
});
//console.log('-- here timeserverid - ' + timeserverid);
    	
// Update record for node server.
var interval = setInterval(function() {
    //	console.log('-- Time server 5 sec update --');                	
    var updatetstablesql = "UPDATE exm_quizaccess_hbmon_timeserver SET lastlivetime = "
	+ Math.floor((new Date().getTime())/1000) 
    //    								+ " WHERE timeserverid = (SELECT * FROM (SELECT MAX(timeserverid) FROM exm_quizaccess_hbmon_timeserver) AS tstable)";
	+ " WHERE timeserverid = " + currenttimeserverid;
    //	console.log(updatetstablesql);
    //console.log('---------- DB conev ts update ----------');
    con.query(updatetstablesql, function(err, result) {
	if (err) throw err;
    });
}, 5000);

// Socket.IO
var record = io.sockets.on('connection', function (socket) {	
    console.log('\n---------- Connect event. Connected sockets - ' + io.sockets.server.eio.clientsCount);
    allsocketscount = io.sockets.server.eio.clientsCount;
    console.log('             Socket connected - ' + socket.id + '. TS - ' + (socket.handshake.issued) + '. Curr. TS - ' + (new Date().getTime()));
    
    var allclients = io.sockets.server.eio.clients;
    allsocketids = [];
    for (var clientid in allclients) {
	// This is the socket of each client in the room.
	allsocketids.push(io.sockets.server.eio.clients[clientid].id);
    }  
    
    socket.on('attempt', function(data) {
	console.log('           Attempt event ----------');
	
	// Append some extra data to the socket object.
	socket.username 		= "'" + data.username + "'";
	socket.quizid 			= data.quizid;
        socket.roomid 			= "'" + data.roomid + "'";
        socket.socketid 		= "'" + socket.id + "'";
        socket.statusConnected 	= "'Connected'";
        //socket.timestampC 		= socket.handshake.issued;
        socket.timestampC1 		= socket.handshake.issued;
        socket.timestampC 		= Math.floor((socket.timestampC1)/1000);
        socket.ip 				= "'" + socket.request.connection.remoteAddress + "'";
	//        cfg = data.config;
        
	//        console.log('-- Time stamps --');
	//        console.log('-- Socket time stamp --' + socket.timestampC);
	//        console.log('-- Current time stamp --' + Math.floor((new Date().getTime())/1000));
	
        var sql = "INSERT INTO exm_quizaccess_hbmon_socketinfo (username, quizid, roomid, socketid, socketstatus, ip, timestamp) VALUES" +
            "(" + socket.username + "," 
            + socket.quizid + "," 
            + socket.roomid + "," 
            + socket.socketid + "," 
            + socket.statusConnected + "," 
            + socket.ip + "," 
            + socket.timestampC + ")";
	con.query(sql, function(err, result) {
	    if (err) throw err;
	});
	//	    console.log('-- Insert done --');
        console.log('           DB conev insert. ' + socket.roomid + ':' + socket.id + ' inserted to DB ----------');
	
	// Join the connected socket to the room. 'roomid' is the concatenation of (username + quizid + attemptid).
	// Here, 'roomid' corresponds to a particular user.
	socket.join(socket.roomid);	   
	
	// Find the total connected sockets in the room.
	roomwisesockets = io.sockets.adapter.rooms[socket.roomid].sockets;
	roomwisesocketids[socket.roomid] = [];
	for (var clientid in roomwisesockets) {
	    // This is the socket of each client in the room.
	    var clientsocket = io.sockets.connected[clientid];
	    roomwisesocketids[socket.roomid].push(clientsocket.id);
	}
	
	totalconnectedsockets = io.sockets.adapter.rooms[socket.roomid].length;   
	
	if (totalconnectedsockets > 0) {
	    socket.currentstatus = "'Live'";
            
	    // 'livetable' reflects current status of a user. There is one entry per user in this table. 
	    // If exists, fetch previous entry for this user from 'livetable'.
	    
	    var liverecordexistsql = "SELECT * FROM exm_quizaccess_hbmon_livetable WHERE roomid = " + socket.roomid;
	    	con.query(liverecordexistsql, function(err, result) {
                    if (err) throw err;                
                    if (result.length > 0) {
			for (i in result) {
                    	    // Previous state details.
                            var status 			= result[i].status;
                            var room_timeserver	= result[i].timeserver;
                            var timetoconsider 	= result[i].timetoconsider;
                            var deadtime 		= result[i].deadtime;
                            var livetime 		= result[i].livetime;
                            //console.log(socket.roomid + ' - Previous deadtime - ' + humanise(deadtime) + ' - Status - ' + status);
                            console.log('            ' + socket.roomid + ' - Previous deadtime - ' + deadtime + ' - Status - ' + status);
			}
			//                    if (status == 'Dead') {
                    	// Time server check.
                    	var timeserver = [];
                    	var timeserverid;
                        var timestarted;
                        var lastlivetime;
                        
                    	if(currenttimeserverid != room_timeserver) {
                    	    //console.log('-- curr. timeserverid -- ' + currenttimeserverid + '-- room tsid -- ' + room_timeserver);
	                    var tssql = "SELECT * FROM exm_quizaccess_hbmon_timeserver WHERE timeserverid IN (" + room_timeserver + ", " + currenttimeserverid + ")";
			    
	                    console.log('-- before tssql --');
	            	    var value = con.query(tssql, function(err, tsresult) {
	                        if (err) throw err;  
	                        var timeserver = [];
	                        if (tsresult.length > 0) {
	                            console.log('-- in tssql --');
	                            for (i in tsresult) {
	                                // Previous state details.
	                                console.log('-- tsresult array -- ' + tsresult[i]);	                                    
	                                timeserver.push({
	                                    timeserverid : tsresult[i].timeserverid,
	                                    timestarted  : tsresult[i].timestarted,
	                                    lastlivetime : tsresult[i].lastlivetime
	                                })
					//	                                    console.log('-- TS data -- ' + timeserverid + ' ' + timestarted + ' ' + lastlivetime);    
	                            }
                                    console.log(1 + '-- timeserver[1].timeserverid -- ' + timeserver[1].timeserverid);
				    
	                            // ------------------------------------------------------------------------------------
	                            // Condition 1 - Server goes down.
	    	            	    var serverdowntime;
	    	            	    
	    	            	    var sdowntimestart = timeserver[0].lastlivetime;
	    	            	    var sdowntimeend = timeserver[1].timestarted;
	    	            	    serverdowntime = sdowntimeend - sdowntimestart;
				    //	    	            	    	serverdowntime = Math.floor((new Date().getTime())/1000) - timeserver[0].lastlivetime;
	    	            	    
	    	            	    var userdowntime;
	    	            	    var udowntimestart = timetoconsider;
	    	            	    var udowntimeend = socket.timestampC;
	    	            	    userdowntime = udowntimeend - udowntimestart;
	    	            	    
	    	            	    //--------------------------------------------------------------------------------------
	    	                    // Condition 2 - Server and user, both go down.
				    //	    	            	    	for (i in timeserver) {
				    //	    	            	    		console.log(i + '-- timeserver array loop -- ' + timeserver[i].timeserverid);
				    //	    	            	    	}
	    	            	    
	    	            	    console.log('-- serverdowntime -- ' + serverdowntime);
	    	            	    console.log('-- userdowntime -- ' + userdowntime);
	    	            	    
	    	            	    var maxdowntime;
	    	            	    maxdowntime = Math.max(serverdowntime, userdowntime);
	    	            	    console.log('-- maxdowntime -- ' + maxdowntime);
	    	            	    
	                	    if(maxdowntime) {
	                	    	console.log('-- before deadtime 2 -- ' + deadtime);
	    	                        deadtime = parseInt(deadtime) + parseInt(maxdowntime);
	    		                console.log('-- deadtime 2 -- ' + deadtime);
	    	                    }


                                   console.log('node down: status to' + socket.currentstatus);
	                	    
		                    var updatelivetablesql = "UPDATE exm_quizaccess_hbmon_livetable SET status = " 	+ socket.currentstatus 
					+ ", deadtime = "  	+ deadtime 
					+ ", timetoconsider = " + socket.timestampC
					+ ", timeserver = " + currenttimeserverid
					+ " WHERE roomid = " + socket.roomid;
				    con.query(updatelivetablesql, function(err, result) {
					if (err) throw err;
				    });
	                        }
	                        return timeserver;
	                        
	            	    });
	            	    
	            	    // con.query output
			    //	            	    	console.log('-- value -- ' + value);
			    //	            	    	for (i in value) {
			    //	            	    		console.log('-- value[' + i + '] -- ' + value[i]);	
			    //	            	    	}
			    //	            	    	
                    	}
	            	//if (status == 'Dead' && currenttimeserverid == room_timeserver) {
	            	if ( currenttimeserverid == room_timeserver) {
			    //                    	} else {
                   	    
	                    //--------------------------------------------------------------------------------------
	                    // Condition 3 - User goes down.
	                    // 	console.log('=========== delta deadtime calc: ' + socket.roomid + ' - Current deadtime is - ' + humanise(socket.timestampC - timetoconsider));
	                    
	                    // Check whether it is a ques switch or not. Ques switch time is approx. betwn. 0-2 secs.
	                    // This is required when there is only one connected socket for that user.
	                    
	                    // Compute cumulative deadtime.
                            var delta = socket.timestampC - timetoconsider;
			    console.log('======= delta deadtime: '+ delta);
			    //if((delta) > 20){
	                        deadtime = parseInt(deadtime) + parseInt(delta);
	                        //console.log('-- deadtime 1 -- ' + deadtime);
	                        
                                   console.log('conn1: status to' + socket.currentstatus  + ' ' + socket.id);
	                        var updatelivetablesql = "UPDATE exm_quizaccess_hbmon_livetable SET status = " 	+ socket.currentstatus 
				    + ", deadtime = " + deadtime 
				    + ", timetoconsider = " + socket.timestampC
				    + ", timeserver = " + currenttimeserverid
				    + " WHERE roomid = " + socket.roomid;
				con.query(updatelivetablesql, function(err, result) {
				    if (err) throw err;
				});
            	    	    //}  
			}
			//                    }
                    } else {
                	// Insert current status entry for this user in 'livetable'.                	
                        console.log('conn2: status to' + socket.currentstatus  + ' ' + socket.id);
			var livetablesql = "INSERT INTO exm_quizaccess_hbmon_livetable (roomid, status, timeserver, timetoconsider, livetime, deadtime) VALUES" +
                            "(" + socket.roomid + "," 
                            + socket.currentstatus + "," 
                            + currenttimeserverid + "," 
                            + socket.timestampC + ", 0, 0 )";
			con.query(livetablesql, function(err, result) {
                            if (err) throw err;
			});
                    }
		});
	}
	socket.emit('timeserver', { currenttimeserverid:currenttimeserverid });
	
	socket.on('error', (error) => {
	    console.log('In \'error\' event. Connected sockets - ' + io.sockets.server.eio.clientsCount);
	});
    });
    
    
    socket.on('disconnect', function() {
	console.log('    ***** In \'disconnect\' event. Connected sockets - ' + io.sockets.server.eio.clientsCount);
	console.log('---------- ' + socket.id + ' disconnected. Curr. TS - ' + (new Date().getTime()));
	
	allsocketscount = io.sockets.server.eio.clientsCount;
	var allclients = io.sockets.server.eio.clients;
	allsocketids = [];
	if (allclients != undefined) {
	    for (var clientid in allclients) {
		// This is the socket of each client in the room.
		allsocketids.push(io.sockets.server.eio.clients[clientid].id);
	    } 
	}
	
	if(socket.roomid != undefined) {			
	    // Construct record for the disconnected socket.
	    socket.timestampD = Math.floor((new Date().getTime())/1000);
	    //			socket.timestampD = time();
	    socket.statusDisconnected = "'Disconnected'";
	    
	    // Insert disconnection record into database.
	    var sql = "INSERT INTO exm_quizaccess_hbmon_socketinfo (username, quizid, roomid, socketid, socketstatus, ip, timestamp) VALUES" +
		"(" + socket.username + "," 
		+ socket.quizid + "," 
		+ socket.roomid + "," 
		+ socket.socketid + "," 
		+ socket.statusDisconnected + "," 
		+ socket.ip + "," 
		+ socket.timestampD + ")";
	    
	    con.query(sql, function(err, result) {
		if (err) throw err;	  
	    });
	    console.log('           DB disconev insert ----------');	    
	    // Find the total connected sockets in the room.
	    var rooms = io.sockets.adapter.rooms[socket.roomid]; 
	    
	    if(typeof rooms != 'undefined') {
	  	totalconnectedsockets = io.sockets.adapter.rooms[socket.roomid].length;  
	  	
	  	// Find the total connected sockets in the room.
	  	roomwisesockets = io.sockets.adapter.rooms[socket.roomid].sockets; 
	  	roomwisesocketids[socket.roomid] = [];
	  	for (var clientid in roomwisesockets) {
	  	    // This is the socket of each client in the room.
	  	    var clientsocket = io.sockets.connected[clientid];
	  	    roomwisesocketids[socket.roomid].push(clientsocket.id);
	  			}
	    } else {
	  	totalconnectedsockets = 0;
	    }
       	    
	    if(totalconnectedsockets == 0) {          
		socket.currentstatus = "'Dead'";

               
		
		// Fetch previous entry for this user from 'livetable'.
		var fetchtimesql = "SELECT * FROM exm_quizaccess_hbmon_livetable WHERE roomid = " + socket.roomid;
		//			    console.log("fetchsql: " + fetchtimesql);
		
		con.query(fetchtimesql, function(err,ftresult) {
		    if (err) throw err;
		    //				console.log("result: ");
		    //				console.log(ftresult);
		    var timetoconsider;
                    var livetime;
		    
		    for (i in ftresult) {
			timetoconsider = ftresult[i].timetoconsider;
			livetime = ftresult[i].livetime;
			//			    		console.log(socket.roomid + ' - Previous livetime - ' + humanise(livetime));
		    }
		    if(livetime == 'undefined'){
			console.log('Erroneous query:');
			console.log(fetchtimesql);
		    }
		    
		    // Here, socket.timestampD is the maxdisconnecttime.
		    //			    	console.log(socket.roomid + ' - Current livetime is - ' + humanise(socket.timestampD - timetoconsider));
		    
		    // Compute cumulative livetime.
		    livetime = parseInt(livetime) + parseInt(socket.timestampD - timetoconsider);
		    //			    	console.log(socket.roomid + ' - After cumulation, livetime  is - ' + humanise(livetime));
		    
		    // Update 'status' entry for this user in 'livetable'.
                        console.log('discon: status to' + socket.currentstatus + ' ' + socket.id);
		    var updatelivetablesql = "UPDATE exm_quizaccess_hbmon_livetable SET status = " + socket.currentstatus 
			+ ", timetoconsider = " + socket.timestampD 
			+ ", livetime = " + livetime 
			+ " where roomid = " + socket.roomid;
		    //				console.log('Err query: ' + updatelivetablesql);
		    con.query(updatelivetablesql, function(err, result) {
			if (err) throw err;
		    });
		});
	    }
	}
    }); 
});

/****
     
     A human redable code of a human redable time in milliseconds
     
****/
function humanise(timems) { // time in milli sec
    var sms = 1000; // millisec in one sec
    var mms = sms*60; // ms in one min
    var hms = mms*60; // ms in one hr
    var dms = hms*24 // ms in one day
    
    var days = Math.floor( timems        / dms); // whole days
    var hrs  = Math.floor((timems % dms) / hms); // whole hours
    var mins = Math.floor((timems % hms) / mms); // whole mins
    var secs = Math.floor((timems % mms) / sms); // whole mins
    var msec = Math.floor((timems % sms)      ); // who

    var dstr  = days > 1 ? ' days' : ' day';
    var hstr  = hrs  > 1 ? ' hrs'  : ' hr';
    var mstr  = mins > 1 ? ' mins' : ' min';
    var sstr  = secs > 1 ? ' secs' : ' sec';
    var msstr = msec > 1 ? ' msecs': ' msec';


    var humstr = '';
    humstr += days == 0 ? '' : days + dstr;
    humstr += hrs == 0 ? '' : ' ' + hrs + hstr;
    humstr += mins == 0 ? '' : ' ' + mins + mstr;
    humstr += secs == 0 ? '' : ' ' + secs + sstr;
    humstr += msec == 0 ? '' : ' ' + msec + msstr;

    return humstr;
}


//console.log(http);
http.on('listening',function(){
    //    console.log('-- Ok, server is running --');
});
//
//http.on('connection', function(socket) {
//    socket.on('data', function(buf) {
//        console.log('received',buf.toString('utf8'));
//    });
//});

http.listen(port, function() {
    //	console.log('-- Listening on port ' + port + ' --');
    
});

