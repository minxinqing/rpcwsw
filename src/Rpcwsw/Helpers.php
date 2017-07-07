<?php
namespace Rpcwsw;

function server($serverName) {
    return \Rpcwsw\Client::instance($serverName);
}
