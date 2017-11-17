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
	password : "root@mysql",
	database : "trial"
});

app.use(bodyParser.urlencoded({ extended: true }));

app.get('/admin-userstatus', function(req, res) {
    res.sendFile(__dirname + '/admin-userstatus.html');
});

var port = 3000;

var record = io.sockets.on('connection', function (socket) {	
	console.log( socket.id + ' connected');
	
  
	
	socket.on('attempt', function(data) {
	
		    socket.username = "'" + data.username + "'";
	        socket.quizid = data.quizid;
	        socket.roomid = "'" + data.roomid + "'";
	        socket.socketid = "'" + socket.id + "'";
	        socket.statusConnected = "'Connected'";
	        socket.timestampC = socket.handshake.issued;
	        socket.ip = "'" + socket.request.connection.remoteAddress + "'";
	        
	     var socketobject = {  username : socket.username, 
	    						quizid : socket.quizid, 
	    						roomid : socket.roomid, 
	    					  socketid : socket.id, 
	    						    ip : socket.ip
	    					};

	    // Insert connection record into database.
	     var sql = "INSERT INTO socketinfo (username,quizid,roomid,socketid,socketstatus,ip,timestamp) VALUES" +
         			"(" + socket.username + "," + socket.quizid + "," + socket.roomid + "," + socket.socketid + 
         			"," + socket.statusConnected + "," + socket.ip + "," + socket.timestampC + ")";
	     con.query(sql, function(err, result) {
			   if (err) throw err;
			});

	    //Joining the connected SID to the ROOM
	    socket.join(socket.roomid);	   
	    //Finding the total connected SIDs to the ROOM
	    var totalconnectedsockets= io.sockets.adapter.rooms[socket.roomid].length;   

	    if (totalconnectedsockets > 0) {
	    	socket.livestatus = "'Live'";
           
	    	var liverecordexistsql = "Select * from livetable1 where roomid="+socket.roomid;
     
	    	con.query(liverecordexistsql, function(err, result) {
                if (err) throw err;
                if (result.length > 0) {

                    for (i in result) {
                        status = result[i].status;
                        timetoconsider = result[i].timetoconsider;
                        var deadtime = result[i].deadtime;
                        console.log(socket.roomid + ': Previous deadtime: ' + humanise(deadtime));
                    }

                    if (status == 'Dead') {
                        console.log(socket.roomid + ': Current Deadtime is :' + humanise(socket.timestampC - timetoconsider));
                        deadtime = parseInt(deadtime) + parseInt(socket.timestampC - timetoconsider);
                        console.log(socket.roomid + ': After cumulation,deadtime is : ' + humanise(deadtime));

                        var updatelivetablesql = "Update livetable1 set status=" + socket.livestatus + ",deadtime=" + deadtime +
                                                 ",timetoconsider=" + socket.timestampC + " where roomid=" + socket.roomid;
                        con.query(updatelivetablesql, function(err, result) {
                            if (err) throw err;
                        });
                    }

                } else {
                    var livetablesql = "INSERT INTO livetable1 (roomid,status,timetoconsider,livetime,deadtime) VALUES" +
                                      "(" + socket.roomid + "," + socket.livestatus + "," + socket.timestampC + ",0,0 )";
                    con.query(livetablesql, function(err, result) {
                        if (err) throw err;
                    });
                }
            });
          }
	    

	});
	
	
	socket.on('disconnect', function() {
		console.log( socket.id + ' disconnected');
				
		// Construct record for socket disconnected.
		socket.timestampD = new Date().getTime();
		socket.statusDisconnected = "'Disconnected'";
	  	    
	    // Insert disconnection record into database.
		var sql = "INSERT INTO socketinfo (username,quizid,roomid,socketid,socketstatus,ip,timestamp) VALUES" +
				  "(" + socket.username + "," + socket.quizid + "," + socket.roomid + "," + socket.socketid + 
				  "," + socket.statusDisconnected + "," + socket.ip + "," + socket.timestampD + ")";

	    con.query(sql, function(err, result) {
	    	if (err) throw err;	  
	    });
	    
	
	    
//	    //This is not providing the live connected sockets,as room is getting empty on disconnection,I guess.
//	    So currently using db,to get the live status.
//	    var clients = io.sockets.adapter.rooms[socket.roomid].sockets;   
//	    
//	    var numClients = (typeof clients !== 'undefined') ? Object.keys(clients).length : 0;
//	    console.log('still there are '+ numClients+ ' connected sockets');
	    
	      var liveordead = "select username,quizid,roomid," +
          "count(case when socketstatus = 'Connected' then socketid end) as count_connected_socketid, " +
          "count(case when socketstatus = 'Disconnected' then socketid end) as count_disconnected_socketid " +
          "from socketinfo where roomid=" + socket.roomid +
          "group by username,quizid,roomid";
      
	      con.query(liveordead, function(err, result) {
          if (err) throw err;
          for (i in result) {
              countC = result[i].count_connected_socketid;
              countD = result[i].count_disconnected_socketid;
          }

          if (countC == countD) {
              socket.livestatus = "'Dead'";

              var fetchtimesql = "select timetoconsider,livetime from livetable1 where roomid=" + socket.roomid;

              con.query(fetchtimesql, function(err, result) {
                  if (err) throw err;

                  for (i in result) {
                      var timetoconsider = result[i].timetoconsider;
                      var livetime = result[i].livetime;
                      console.log(socket.roomid + ':Previous livetime: ' + humanise(livetime));
                  }

                  //  here socket.timestampD is the maxdisconnecttime;
                  console.log(socket.roomid + ':Current livetime is :' + humanise(socket.timestampD - timetoconsider));
                  livetime = parseInt(livetime) + parseInt(socket.timestampD - timetoconsider);
                  console.log(socket.roomid + ':After cumulation,livetime  is :' + humanise(livetime));

                  var updatelivetablesql = "Update livetable1 set status=" + socket.livestatus + ", timetoconsider=" +
                      socket.timestampD + ",livetime=" + livetime + "  where roomid=" + socket.roomid;

                  con.query(updatelivetablesql, function(err, result) {
                      if (err) throw err;
                  });
              });
          }
      });
  
	});
   
});

function humanise(difference) {
    var days = Math.floor(difference / (1000 * 60 * 60 * 24));
    var hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((difference % (1000 * 60)) / 1000);
    var time = days + ' days, ' + hours + ' hrs, ' + minutes + ' mins, ' + seconds + ' secs';
    return time;
}


app.get('/livestatus', function(req, res) {
    var sql = "select * from livetable1 ";
    con.query(sql, function(err, result, fields) {
        if (err) throw err;
        res.send(result);
    });
});

http.listen(port, function() {
	console.log('Listening on port ' + port);
});