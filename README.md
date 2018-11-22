# moodle-quizaccess_heartbeatmonitor

This is a plugin to automatically extend the quiz time limit for users who encounter a network or a hardware failure. 

## Background
In a quiz where some time gets wasted due to issues like network failures or hardware crash, the user needs to be given additional time to compensate fairly for this loss.	 

## Problem
* There is no way to automatically quantify the lost time.
* The compensation of the lost time has to be done manually with the help of user overrides. 

## Proposed solution
* Monitor the health of the connection using heartbeats provided by websockets.
* Record the connections and disconnections of  a user attempting a quiz.
* Calculate the time lost between connections.
* Automatically set user-overrides to extend the quiz time limit by the amount of time lost.

## Implementation details
* NodeJS, ExpressJS, Socket.IO for heartbeat tracking.
* Trigger moodle to set user-overrides

