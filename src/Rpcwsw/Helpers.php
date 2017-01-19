<?php

function rpcwsw($serverName) {
    return \Rpcwsw\Client::instance($serverName);
}