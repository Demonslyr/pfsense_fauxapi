<?php
/**
 * FauxAPI
 *  - A REST API interface for pfSense to facilitate dev-ops.
 *  - https://github.com/ndejong/pfsense_fauxapi
 * 
 * Copyright 2016 Nicholas de Jong  
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once('util.inc');
require_once('guiconfig.inc');

$pgtitle = array(gettext('System'), gettext('FauxAPI'), gettext('About'));
include_once('head.inc');

$tab_array   = array();
$tab_array[] = array(gettext("Credentials"), false, "/fauxapi/admin/credentials.php");
$tab_array[] = array(gettext("Logs"), false, "/fauxapi/admin/logs.php");
$tab_array[] = array(gettext("About"), true, "/fauxapi/admin/about.php");
display_top_tabs($tab_array, true);

?>

<script type="text/javascript">
// Kludge to cause the <pre> background to be white making it easier to read
//<![CDATA[
events.push(function() {
    $('pre').css('background-color', '#fff')
})
//]]>
</script>

<div>
<!--READMESTART-->
<h1>
<a id="user-content-fauxapi---v14" class="anchor" href="#fauxapi---v14" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>FauxAPI - v1.4</h1>
<p>A REST API interface for pfSense 2.3.x, 2.4.x, 2.5.x to facilitate devops:-</p>
<ul>
<li><a href="https://github.com/ndejong/pfsense_fauxapi">https://github.com/ndejong/pfsense_fauxapi</a></li>
</ul>
<p>Additionally available are a set of <a href="#client-libraries">client libraries</a>
that hence make programmatic access and management of pfSense hosts for devops
tasks feasible.</p>
<h2>
<a id="user-content-api-action-summary" class="anchor" href="#api-action-summary" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>API Action Summary</h2>
<ul>
<li>
<a href="#user-content-alias_update_urltables">alias_update_urltables</a> - Causes the pfSense host to immediately update any urltable alias entries from their (remote) source URLs.</li>
<li>
<a href="#user-content-config_backup">config_backup</a> - Causes the system to take a configuration backup and add it to the regular set of system change backups.</li>
<li>
<a href="#user-content-config_backup_list">config_backup_list</a> - Returns a list of the currently available system configuration backups.</li>
<li>
<a href="#user-content-config_get">config_get</a> - Returns the full system configuration as a JSON formatted string.</li>
<li>
<a href="#user-content-config_patch">config_patch</a> - Patch the system config with a granular piece of new configuration.</li>
<li>
<a href="#user-content-config_reload">config_reload</a> - Causes the pfSense system to perform an internal reload of the <code>config.xml</code> file.</li>
<li>
<a href="#user-content-config_restore">config_restore</a> - Restores the pfSense system to the named backup configuration.</li>
<li>
<a href="#user-content-config_set">config_set</a> - Sets a full system configuration and (by default) reloads once successfully written and tested.</li>
<li>
<a href="#user-content-function_call">function_call</a> - Call directly a pfSense PHP function with API user supplied parameters.</li>
<li>
<a href="#user-content-gateway_status">gateway_status</a> - Returns gateway status data.</li>
<li>
<a href="#user-content-interface_stats">interface_stats</a> - Returns statistics and information about an interface.</li>
<li>
<a href="#user-content-rule_get">rule_get</a> - Returns the numbered list of loaded pf rules from a <code>pfctl -sr -vv</code> command on the pfSense host.</li>
<li>
<a href="#user-content-send_event">send_event</a> - Performs a pfSense "send_event" command to cause various pfSense system actions.</li>
<li>
<a href="#user-content-system_reboot">system_reboot</a> - Reboots the pfSense system.</li>
<li>
<a href="#user-content-system_stats">system_stats</a> - Returns various useful system stats.</li>
<li>
<a href="#user-content-system_info">system_info</a> - Returns various useful system info.</li>
</ul>
<h2>
<a id="user-content-approach" class="anchor" href="#approach" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Approach</h2>
<p>At its core FauxAPI simply reads the core pfSense <code>config.xml</code> file, converts it
to JSON and returns to the API caller.  Similarly it can take a JSON formatted
configuration and write it to the pfSense <code>config.xml</code> and handles the required
reload operations.  The ability to programmatically interface with a running
pfSense host(s) is enormously useful however it should also be obvious that this
provides the API user the ability to create configurations that can break your
pfSense system.</p>
<p>FauxAPI provides easy backup and restore API interfaces that by default store
configuration backups on all configuration write operations thus it is very easy
to roll-back even if the API user manages to deploy a "very broken" configuration.</p>
<p>Multiple sanity checks take place to make sure a user provided JSON config will
correctly convert into the (slightly quirky) pfSense XML <code>config.xml</code> format and
then reload as expected in the same way.  However, because it is not a real
per-action application-layer interface it is still possible for the API caller
to create configuration changes that make no sense and can potentially disrupt
your pfSense system - as the package name states, it is a "Faux" API to pfSense
filling a gap in functionality with the current pfSense product.</p>
<p>Because FauxAPI is a utility that interfaces with the pfSense <code>config.xml</code> there
are some cases where reloading the configuration file is not enough and you
may need to "tickle" pfSense a little more to do what you want.  This is not
common however a good example is getting newly defined network interfaces or
VLANs to be recognized.  These situations are easily handled by calling the
<strong>send_event</strong> action with the payload <strong>interface reload all</strong> - see the example
included below and refer to a the resolution to <a href="https://github.com/ndejong/pfsense_fauxapi/issues/10">Issue #10</a></p>
<p><strong>NB:</strong> <em>As at FauxAPI v1.2 the <strong>function_call</strong> action has been introduced that
now provides the ability to issue function calls directly into pfSense.</em></p>
<h2>
<a id="user-content-installation" class="anchor" href="#installation" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Installation</h2>
<p>Until the FauxAPI is added to the pfSense FreeBSD-ports tree you will need to
install manually from <strong>root</strong> as shown:-</p>
<div class="highlight highlight-source-shell"><pre><span class="pl-c1">set</span> fauxapi_base_package_url=<span class="pl-s"><span class="pl-pds">'</span>https://raw.githubusercontent.com/ndejong/pfsense_fauxapi_packages/master<span class="pl-pds">'</span></span>
<span class="pl-c1">set</span> fauxapi_latest=<span class="pl-s"><span class="pl-pds">`</span>fetch -qo - <span class="pl-smi">${fauxapi_base_package_url}</span>/LATEST<span class="pl-pds">`</span></span>
fetch <span class="pl-smi">${fauxapi_base_package_url}</span>/<span class="pl-smi">${fauxapi_latest}</span>
pkg-static install <span class="pl-smi">${fauxapi_latest}</span></pre></div>
<p>Installation and de-installation is quite straight forward, further examples can
be found in the <code>README.md</code> located <a href="https://github.com/ndejong/pfsense_fauxapi_packages">here</a>.</p>
<p>Refer to the published package <a href="https://github.com/ndejong/pfsense_fauxapi_packages/blob/master/SHA256SUMS"><code>SHA256SUMS</code></a></p>
<p><strong>Hint:</strong> if not already, consider installing the <code>jq</code> tool on your local machine (not
pfSense host) to pipe and manage JSON outputs from FauxAPI - <a href="https://stedolan.github.io/jq/" rel="nofollow">https://stedolan.github.io/jq/</a></p>
<p><strong>NB:</strong> you MUST at least setup your <code>/etc/fauxapi/credentials.ini</code> file on the
pfSense host before you continue, see the API Authentication section below.</p>
<h2>
<a id="user-content-client-libraries" class="anchor" href="#client-libraries" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Client libraries</h2>
<h4>
<a id="user-content-python" class="anchor" href="#python" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Python</h4>
<p>A <a href="https://github.com/ndejong/pfsense_fauxapi_client_python">Python interface</a>
to pfSense was perhaps the most desired end-goal at the onset of the FauxAPI
package project.  Anyone that has tried to parse the pfSense <code>config.xml</code> files
using a Python based library will understand that things don't quite work out as
expected or desired.</p>
<p>The Python client-library can be easily installed from PyPi as such</p>
<div class="highlight highlight-source-shell"><pre>pip3 install pfsense-fauxapi</pre></div>
<p>Package Status: <a href="https://pypi.org/project/pfsense-fauxapi/" rel="nofollow"><img src="https://camo.githubusercontent.com/93156147a029f0a7f529402e128e26ef49cdfacd/68747470733a2f2f696d672e736869656c64732e696f2f707970692f762f706673656e73652d666175786170692e737667" alt="PyPi" data-canonical-src="https://img.shields.io/pypi/v/pfsense-fauxapi.svg" style="max-width:100%;"></a> <a href="https://travis-ci.org/ndejong/pfsense_fauxapi_client_python" rel="nofollow"><img src="https://camo.githubusercontent.com/0ea636b454052bc8cd9abed19b35b11d13e739a4/68747470733a2f2f7472617669732d63692e6f72672f6e64656a6f6e672f706673656e73655f666175786170695f636c69656e745f707974686f6e2e7376673f6272616e63683d6d6173746572" alt="Build Status" data-canonical-src="https://travis-ci.org/ndejong/pfsense_fauxapi_client_python.svg?branch=master" style="max-width:100%;"></a></p>
<p>Use of the package should be easy enough as shown</p>
<div class="highlight highlight-source-python"><pre><span class="pl-k">import</span> <span class="pl-s1">pprint</span>, <span class="pl-s1">sys</span>
<span class="pl-k">from</span> <span class="pl-v">PfsenseFauxapi</span>.<span class="pl-v">PfsenseFauxapi</span> <span class="pl-k">import</span> <span class="pl-v">PfsenseFauxapi</span>
<span class="pl-v">PfsenseFauxapi</span> <span class="pl-c1">=</span> <span class="pl-v">PfsenseFauxapi</span>(<span class="pl-s">'&lt;host-address&gt;'</span>, <span class="pl-s">'&lt;fauxapi-key&gt;'</span>, <span class="pl-s">'&lt;fauxapi-secret&gt;'</span>)

<span class="pl-s1">aliases</span> <span class="pl-c1">=</span> <span class="pl-v">PfsenseFauxapi</span>.<span class="pl-en">config_get</span>(<span class="pl-s">'aliases'</span>)
<span class="pl-c">## perform some kind of manipulation to `aliases` here ##</span>
<span class="pl-s1">pprint</span>.<span class="pl-en">pprint</span>(<span class="pl-v">PfsenseFauxapi</span>.<span class="pl-en">config_set</span>(<span class="pl-s1">aliases</span>, <span class="pl-s">'aliases'</span>))</pre></div>
<p>It is recommended to review the <a href="https://github.com/ndejong/pfsense_fauxapi_client_python/tree/master/examples">Python code examples</a>
to observe worked examples with the client library.  Of small note is that the
Python library supports the ability to get and set single sections of the pfSense
system, not just the entire system configuration as with the Bash library.</p>
<p><strong>Python examples</strong></p>
<ul>
<li>
<code>usergroup-management.py</code> - example code that provides the ability to <code>get_users</code>,
<code>add_user</code>, <code>manage_user</code>, <code>remove_user</code> and perform the same functions on groups.</li>
<li>
<code>update-aws-aliases.py</code> - example code that pulls in the latest AWS <code>ip-ranges.json</code>
data, parses it and injects them into the pfSense aliases section if required.</li>
<li>
<code>function-iterate.py</code> - iterates (almost) all the FauxAPI functions to
confirm operation.</li>
</ul>
<h4>
<a id="user-content-command-line" class="anchor" href="#command-line" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Command Line</h4>
<p>As distinct from the Bash library as described below the Python pip also introduces
a command-line tool to interact with the API, which makes a wide range of actions
possible directly from the command line, for example</p>
<div class="highlight highlight-source-shell"><pre>fauxapi --host 192.168.1.200 gateway_status <span class="pl-k">|</span> jq <span class="pl-c1">.</span></pre></div>
<h4>
<a id="user-content-bash" class="anchor" href="#bash" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Bash</h4>
<p>The <a href="https://github.com/ndejong/pfsense_fauxapi_client_bash">Bash client library</a>
makes it possible to add a line with <code>source pfsense-fauxapi.sh</code> to your bash script
and then access a pfSense host configuration directly as a JSON string</p>
<div class="highlight highlight-source-shell"><pre><span class="pl-c1">source</span> pfsense-fauxapi.sh
<span class="pl-k">export</span> fauxapi_auth=<span class="pl-s"><span class="pl-pds">$(</span>fauxapi_auth <span class="pl-k">&lt;</span>fauxapi-key<span class="pl-k">&gt;</span> <span class="pl-k">&lt;</span>fauxapi-secret<span class="pl-k">&gt;</span><span class="pl-pds">)</span></span>

fauxapi_config_get <span class="pl-k">&lt;</span>host-address<span class="pl-k">&gt;</span> <span class="pl-k">|</span> jq .data.config <span class="pl-k">&gt;</span> /tmp/config.json
<span class="pl-c"><span class="pl-c">#</span># perform some kind of manipulation to `/tmp/config.json` here ##</span>
fauxapi_config_set <span class="pl-k">&lt;</span>host-address<span class="pl-k">&gt;</span> /tmp/config.json</pre></div>
<p>It is recommended to review the commented out samples in the provided
<code>fauxapi-sample.sh</code> file that cover all possible FauxAPI calls to gain a better
idea on usage.</p>
<h4>
<a id="user-content-nodejstypescript" class="anchor" href="#nodejstypescript" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>NodeJS/TypeScript</h4>
<p>A NodeJS client has been developed by a third party and is available here</p>
<ul>
<li>NPMJS: <a href="https://www.npmjs.com/package/faux-api-client" rel="nofollow">npmjs.com/package/faux-api-client</a>
</li>
<li>Github: <a href="https://github.com/Elucidia/faux-api-client">github.com/Elucidia/faux-api-client</a>
</li>
</ul>
<h4>
<a id="user-content-php" class="anchor" href="#php" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>PHP</h4>
<p>A PHP client has been developed by a third party and is available here</p>
<ul>
<li>Packagist: <a href="https://packagist.org/packages/travisghansen/pfsense_fauxapi_php_client" rel="nofollow">packagist.org/packages/travisghansen/pfsense_fauxapi_php_client</a>
</li>
<li>Github: <a href="https://github.com/travisghansen/pfsense_fauxapi_php_client">github.com/travisghansen/pfsense_fauxapi_php_client</a>
</li>
</ul>
<h2>
<a id="user-content-api-authentication" class="anchor" href="#api-authentication" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>API Authentication</h2>
<p>A deliberate design decision to decouple FauxAPI authentication from both the
pfSense user authentication and the pfSense <code>config.xml</code> system.  This was done
to limit the possibility of an accidental API change that removes access to the
host.  It also seems more prudent to only establish API user(s) manually via the
FauxAPI <code>/etc/fauxapi/credentials.ini</code> file - happy to receive feedback about
this approach.</p>
<p>The two sample FauxAPI keys (PFFAexample01 and PFFAexample02) and their
associated secrets in the sample <code>credentials.sample.ini</code> file are hard-coded to
be inoperative, you must create entirely new values before your client scripts
will be able to issue commands to FauxAPI.</p>
<p>You can start your own <code>/etc/fauxapi/credentials.ini</code> file by copying the sample
file provided in <code>credentials.sample.ini</code></p>
<p>API authentication itself is performed on a per-call basis with the auth value
inserted as an additional <strong>fauxapi-auth</strong> HTTP request header, it can be
calculated as such:-</p>
<pre><code>fauxapi-auth: &lt;apikey&gt;:&lt;timestamp&gt;:&lt;nonce&gt;:&lt;hash&gt;

For example:-
fauxapi-auth: PFFA4797d073:20161119Z144328:833a45d8:9c4f96ab042f5140386178618be1ae40adc68dd9fd6b158fb82c99f3aaa2bb55
</code></pre>
<p>Where the &lt;hash&gt; value is calculated like so:-</p>
<pre><code>&lt;hash&gt; = sha256(&lt;apisecret&gt;&lt;timestamp&gt;&lt;nonce&gt;)
</code></pre>
<p>NB: that the timestamp value is internally passed to the PHP <code>strtotime</code> function
which can interpret a wide variety of timestamp formats together with a timezone.
A nice tidy timestamp format that the <code>strtotime</code> PHP function is able to process
can be obtained using bash command <code>date --utc +%Y%m%dZ%H%M%S</code> where the <code>Z</code>
date-time seperator hence also specifies the UTC timezone.</p>
<p>This is all handled in the <a href="#client-libraries">client libraries</a>
provided, but as can be seen it is relatively easy to implement even in a Bash
shell script.</p>
<p>Getting the API credentials right seems to be a common source of confusion in
getting started with FauxAPI because the rules about valid API keys and secret
values are pedantic to help make ensure poor choices are not made.</p>
<p>The API key + API secret values that you will need to create in <code>/etc/fauxapi/credentials.ini</code>
have the following rules:-</p>
<ul>
<li>&lt;apikey_value&gt; and &lt;apisecret_value&gt; may have alphanumeric chars ONLY!</li>
<li>&lt;apikey_value&gt; MUST start with the prefix PFFA (pfSense Faux API)</li>
<li>&lt;apikey_value&gt; MUST be &gt;= 12 chars AND &lt;= 40 chars in total length</li>
<li>&lt;apisecret_value&gt; MUST be &gt;= 40 chars AND &lt;= 128 chars in length</li>
<li>you must not use the sample key/secret in the <code>credentials.ini</code> since they
are hard coded to fail.</li>
</ul>
<p>To make things easier consider using the following shell commands to generate
valid values:-</p>
<h4>
<a id="user-content-apikey_value" class="anchor" href="#apikey_value" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>apikey_value</h4>
<div class="highlight highlight-source-shell"><pre><span class="pl-c1">echo</span> PFFA<span class="pl-s"><span class="pl-pds">`</span>head /dev/urandom <span class="pl-k">|</span> base64 -w0 <span class="pl-k">|</span> tr -d /+= <span class="pl-k">|</span> head -c 20<span class="pl-pds">`</span></span></pre></div>
<h4>
<a id="user-content-apisecret_value" class="anchor" href="#apisecret_value" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>apisecret_value</h4>
<div class="highlight highlight-source-shell"><pre><span class="pl-c1">echo</span> <span class="pl-s"><span class="pl-pds">`</span>head /dev/urandom <span class="pl-k">|</span> base64 -w0 <span class="pl-k">|</span> tr -d /+= <span class="pl-k">|</span> head -c 60<span class="pl-pds">`</span></span></pre></div>
<p>NB: Make sure the client side clock is within 60 seconds of the pfSense host
clock else the auth token values calculated by the client will not be valid - 60
seconds seems tight, however, provided you are using NTP to look after your
system time it's quite unlikely to cause issues - happy to receive feedback
about this.</p>
<p><strong>Shout Out:</strong> <em>Seeking feedback on the API authentication, many developers
seem to stumble here - if you feel something could be improved without compromising
security then submit an Issue ticket via Github.</em></p>
<h2>
<a id="user-content-api-authorization" class="anchor" href="#api-authorization" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>API Authorization</h2>
<p>The file <code>/etc/fauxapi/credentials.ini</code> additionally provides a method to restrict
the API actions available to the API key using the <strong>permit</strong> configuration
parameter.  Permits are comma delimited and may contain * wildcards to match more
than one rule as shown in the example below.</p>
<pre><code>[PFFAexample01]
secret = abcdefghijklmnopqrstuvwxyz0123456789abcd
permit = alias_*, config_*, gateway_*, rule_*, send_*, system_*, function_*
comment = example key PFFAexample01 - hardcoded to be inoperative
</code></pre>
<h2>
<a id="user-content-debugging" class="anchor" href="#debugging" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Debugging</h2>
<p>FauxAPI comes with awesome debug logging capability, simply insert <code>__debug=true</code>
as a URL request parameter and the response data will contain rich debugging log
data about the flow of the request.</p>
<p>If you are looking for more debugging at various points feel free to submit a
pull request or lodge an issue describing your requirement and I'll see what
can be done to accommodate.</p>
<h2>
<a id="user-content-logging" class="anchor" href="#logging" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Logging</h2>
<p>FauxAPI actions are sent to the system syslog via a call to the PHP <code>syslog()</code>
function thus causing all FauxAPI actions to be logged and auditable on a per
action (callid) basis which provide the full basis for the call, for example:-</p>
<pre lang="text"><code>Jul  3 04:37:59 pfSense php-fpm[55897]: {"INFO":"20180703Z043759 :: fauxapi\\v1\\fauxApi::__call","DATA":{"user_action":"alias_update_urltables","callid":"5b3afda73e7c9","client_ip":"192.168.1.5"},"source":"fauxapi"}
Jul  3 04:37:59 pfSense php-fpm[55897]: {"INFO":"20180703Z043759 :: valid auth for call","DATA":{"apikey":"PFFAdevtrash","callid":"5b3afda73e7c9","client_ip":"192.168.1.5"},"source":"fauxapi"}
</code></pre>
<p>Enabling debugging yields considerably more logging data to assist with tracking
down issues if you encounter them - you may review the logs via the pfSense GUI
as usual unser Status-&gt;System Logs-&gt;General or via the console using the <code>clog</code> tool</p>
<div class="highlight highlight-source-shell"><pre>$ clog /var/log/system.log <span class="pl-k">|</span> grep fauxapi</pre></div>
<h2>
<a id="user-content-configuration-backups" class="anchor" href="#configuration-backups" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Configuration Backups</h2>
<p>All configuration edits through FauxAPI create configuration backups in the
same way as pfSense does with the webapp GUI.</p>
<p>These backups are available in the same way as edits through the pfSense
GUI and are thus able to be reviewed and diff'd in the same way under
Diagnostics-&gt;Backup &amp; Restore-&gt;Config History.</p>
<p>Changes made through the FauxAPI carry configuration change descriptions that
name the unique <code>callid</code> which can then be tied to logs if required for full
usage audit and change tracking.</p>
<p>FauxAPI functions that cause write operations to the system config <code>config.xml</code>
return reference to a backup file of the configuration immediately previous
to the change.</p>
<h2>
<a id="user-content-api-rest-actions" class="anchor" href="#api-rest-actions" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>API REST Actions</h2>
<p>The following REST based API actions are provided, example cURL call request
examples are provided for each.  The API user is perhaps more likely to interface
with the <a href="#client-libraries">client libraries</a> as documented above
rather than directly with these REST end-points.</p>
<p>The framework around the FauxAPI has been put together with the idea of being
able to easily add more actions at a later time, if you have ideas for actions
that might be useful be sure to get in contact.</p>
<p>NB: the cURL requests below use the '--insecure' switch because many pfSense
deployments do not deploy certificate chain signed SSL certificates.  A reasonable
improvement in this regard might be to implement certificate pinning at the
client side to hence remove scope for man-in-middle concerns.</p>
<hr>
<h3>
<a id="user-content-alias_update_urltables" class="anchor" href="#alias_update_urltables" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>alias_update_urltables</h3>
<ul>
<li>Causes the pfSense host to immediately update any urltable alias entries from their (remote)
source URLs.  Optionally update just one table by specifying the table name, else all
tables are updated.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params:
<ul>
<li>
<strong>table</strong> (optional, default = null)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=alias_update_urltables<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"598ec756b4d09"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"alias_update_urltables"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
  <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
    <span class="pl-s">"updates"</span>: <span class="pl-kos">{</span>
      <span class="pl-s">"bruteforceblocker"</span>: <span class="pl-kos">{</span>
        <span class="pl-s">"url"</span>: <span class="pl-s">"https://raw.githubusercontent.com/firehol/blocklist-ipsets/master/bruteforceblocker.ipset"</span><span class="pl-kos">,</span>
        <span class="pl-s">"status"</span>: <span class="pl-kos">[</span>
          <span class="pl-s">"no changes."</span>
        <span class="pl-kos">]</span>
      <span class="pl-kos">}</span>
    <span class="pl-kos">}</span>
  <span class="pl-kos">}</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-config_backup" class="anchor" href="#config_backup" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_backup</h3>
<ul>
<li>Causes the system to take a configuration backup and add it to the regular
set of pfSense system backups at <code>/cf/conf/backup/</code>
</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_backup<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"583012fea254f"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"config_backup"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
  <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
    <span class="pl-s">"backup_config_file"</span>: <span class="pl-s">"/cf/conf/backup/config-1479545598.xml"</span>
  <span class="pl-kos">}</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-config_backup_list" class="anchor" href="#config_backup_list" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_backup_list</h3>
<ul>
<li>Returns a list of the currently available pfSense system configuration backups.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_backup_list<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"583065cb670db"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"config_backup_list"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
  <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
    <span class="pl-s">"backup_files"</span>: <span class="pl-kos">[</span>
      <span class="pl-kos">{</span>
        <span class="pl-s">"filename"</span>: <span class="pl-s">"/cf/conf/backup/config-1479545598.xml"</span><span class="pl-kos">,</span>
        <span class="pl-s">"timestamp"</span>: <span class="pl-s">"20161119Z144635"</span><span class="pl-kos">,</span>
        <span class="pl-s">"description"</span>: <span class="pl-s">"fauxapi-PFFA4797d073@192.168.10.10: update via fauxapi for callid: 583012fea254f"</span><span class="pl-kos">,</span>
        <span class="pl-s">"version"</span>: <span class="pl-s">"15.5"</span><span class="pl-kos">,</span>
        <span class="pl-s">"filesize"</span>: <span class="pl-c1">18535</span>
      <span class="pl-kos">}</span><span class="pl-kos">,</span>
      ...<span class="pl-kos">.</span></pre></div>
<hr>
<h3>
<a id="user-content-config_get" class="anchor" href="#config_get" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_get</h3>
<ul>
<li>Returns the system configuration as a JSON formatted string.  Additionally,
using the optional <strong>config_file</strong> parameter it is possible to retrieve backup
configurations by providing the full path to it under the <code>/cf/conf/backup</code>
path.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params:
<ul>
<li>
<strong>config_file</strong> (optional, default=<code>/cf/config/config.xml</code>)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_get<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
    <span class="pl-s">"callid"</span>: <span class="pl-s">"583012fe39f79"</span><span class="pl-kos">,</span>
    <span class="pl-s">"action"</span>: <span class="pl-s">"config_get"</span><span class="pl-kos">,</span>
    <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
    <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
      <span class="pl-s">"config_file"</span>: <span class="pl-s">"/cf/conf/config.xml"</span><span class="pl-kos">,</span>
      <span class="pl-s">"config"</span>: <span class="pl-kos">{</span>
        <span class="pl-s">"version"</span>: <span class="pl-s">"15.5"</span><span class="pl-kos">,</span>
        <span class="pl-s">"staticroutes"</span>: <span class="pl-s">""</span><span class="pl-kos">,</span>
        <span class="pl-s">"snmpd"</span>: <span class="pl-kos">{</span>
          <span class="pl-s">"syscontact"</span>: <span class="pl-s">""</span><span class="pl-kos">,</span>
          <span class="pl-s">"rocommunity"</span>: <span class="pl-s">"public"</span><span class="pl-kos">,</span>
          <span class="pl-s">"syslocation"</span>: <span class="pl-s">""</span>
        <span class="pl-kos">}</span><span class="pl-kos">,</span>
        <span class="pl-s">"shaper"</span>: <span class="pl-s">""</span><span class="pl-kos">,</span>
        <span class="pl-s">"installedpackages"</span>: <span class="pl-kos">{</span>
          <span class="pl-s">"pfblockerngsouthamerica"</span>: <span class="pl-kos">{</span>
            <span class="pl-s">"config"</span>: <span class="pl-kos">[</span>
             ...<span class="pl-kos">.</span></pre></div>
<p>Hint: use <code>jq</code> to parse the response JSON and obtain the config only, as such:-</p>
<div class="highlight highlight-source-shell"><pre>cat /tmp/faux-config-get-output-from-curl.json <span class="pl-k">|</span> jq .data.config <span class="pl-k">&gt;</span> /tmp/config.json</pre></div>
<hr>
<h3>
<a id="user-content-config_patch" class="anchor" href="#config_patch" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_patch</h3>
<ul>
<li>Allows the API user to patch the system configuration with the existing system config</li>
<li>A <strong>config_patch</strong> call allows the API user to supply the partial configuration to be updated
which is quite different to the <strong>config_set</strong> function that requires the full configuration
to be posted.</li>
<li>HTTP: <strong>POST</strong>
</li>
<li>Params:
<ul>
<li>
<strong>do_backup</strong> (optional, default = true)</li>
<li>
<strong>do_reload</strong> (optional, default = true)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>cat <span class="pl-k">&gt;</span> /tmp/config_patch.json <span class="pl-s"><span class="pl-k">&lt;&lt;</span><span class="pl-k">EOF</span></span>
<span class="pl-s">{</span>
<span class="pl-s">  "system": {</span>
<span class="pl-s">    "dnsserver": [</span>
<span class="pl-s">      "8.8.8.8",</span>
<span class="pl-s">      "8.8.4.4"</span>
<span class="pl-s">    ],</span>
<span class="pl-s">    "hostname": "newhostname"</span>
<span class="pl-s">  }</span>
<span class="pl-s">}</span>
<span class="pl-s"><span class="pl-k">EOF</span></span>

curl \
    -X POST \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    --header <span class="pl-s"><span class="pl-pds">"</span>Content-Type: application/json<span class="pl-pds">"</span></span> \
    --data @/tmp/config_patch.json \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_patch<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"5b3b506f72670"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"config_patch"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
  <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
    <span class="pl-s">"do_backup"</span>: <span class="pl-c1">true</span><span class="pl-kos">,</span>
    <span class="pl-s">"do_reload"</span>: <span class="pl-c1">true</span><span class="pl-kos">,</span>
    <span class="pl-s">"previous_config_file"</span>: <span class="pl-s">"/cf/conf/backup/config-1530613871.xml"</span>
  <span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-config_reload" class="anchor" href="#config_reload" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_reload</h3>
<ul>
<li>Causes the pfSense system to perform a reload action of the <code>config.xml</code> file, by
default this happens when the <strong>config_set</strong> action occurs hence there is
normally no need to explicitly call this after a <strong>config_set</strong> action.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_reload<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"5831226e18326"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"config_reload"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-config_restore" class="anchor" href="#config_restore" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_restore</h3>
<ul>
<li>Restores the pfSense system to the named backup configuration.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params:
<ul>
<li>
<strong>config_file</strong> (required, full path to the backup file to restore)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_restore&amp;config_file=/cf/conf/backup/config-1479545598.xml<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"583126192a789"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"config_restore"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
  <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
    <span class="pl-s">"config_file"</span>: <span class="pl-s">"/cf/conf/backup/config-1479545598.xml"</span>
  <span class="pl-kos">}</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-config_set" class="anchor" href="#config_set" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>config_set</h3>
<ul>
<li>Sets a full system configuration and (by default) takes a system config
backup and (by default) causes the system config to be reloaded once
successfully written and tested.</li>
<li>NB1: be sure to pass the <em>FULL</em> system configuration here, not just the piece you
wish to adjust!  Consider the <strong>config_patch</strong> or <strong>config_item_set</strong> functions if
you wish to adjust the configuration in more granular ways.</li>
<li>NB2: if you are pulling down the result of a <code>config_get</code> call, be sure to parse that
response data to obtain the config data only under the key <code>.data.config</code>
</li>
<li>HTTP: <strong>POST</strong>
</li>
<li>Params:
<ul>
<li>
<strong>do_backup</strong> (optional, default = true)</li>
<li>
<strong>do_reload</strong> (optional, default = true)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X POST \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    --header <span class="pl-s"><span class="pl-pds">"</span>Content-Type: application/json<span class="pl-pds">"</span></span> \
    --data @/tmp/config.json \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=config_set<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"5b3b50e8b1bc6"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"config_set"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
  <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
    <span class="pl-s">"do_backup"</span>: <span class="pl-c1">true</span><span class="pl-kos">,</span>
    <span class="pl-s">"do_reload"</span>: <span class="pl-c1">true</span><span class="pl-kos">,</span>
    <span class="pl-s">"previous_config_file"</span>: <span class="pl-s">"/cf/conf/backup/config-1530613992.xml"</span>
  <span class="pl-kos">}</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-function_call" class="anchor" href="#function_call" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>function_call</h3>
<ul>
<li>Call directly a pfSense PHP function with API user supplied parameters.  Note
that is action is a <em>VERY</em> raw interface into the inner workings of pfSense
and it is not recommended for API users that do not have a solid understanding
of PHP and pfSense.  Additionally, not all pfSense functions are appropriate
to be called through the FauxAPI and only very limited testing has been
performed against the possible outcomes and responses.  It is possible to
harm your pfSense system if you do not 100% understand what is going on.</li>
<li>Functions to be called via this interface <em>MUST</em> be defined in the file
<code>/etc/pfsense_function_calls.txt</code> only a handful very basic and
read-only pfSense functions are enabled by default.</li>
<li>You can start your own <code>/etc/fauxapi/pfsense_function_calls.txt</code> file by
copying the sample file provided in <code>pfsense_function_calls.sample.txt</code>
</li>
<li>HTTP: <strong>POST</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X POST \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    --header <span class="pl-s"><span class="pl-pds">"</span>Content-Type: application/json<span class="pl-pds">"</span></span> \
    --data <span class="pl-s"><span class="pl-pds">"</span>{<span class="pl-cce">\"</span>function<span class="pl-cce">\"</span>: <span class="pl-cce">\"</span>get_services<span class="pl-cce">\"</span>}<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=function_call<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"59a29e5017905"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"function_call"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
  <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
    <span class="pl-s">"return"</span>: <span class="pl-kos">[</span>
      <span class="pl-kos">{</span>
        <span class="pl-s">"name"</span>: <span class="pl-s">"unbound"</span><span class="pl-kos">,</span>
        <span class="pl-s">"description"</span>: <span class="pl-s">"DNS Resolver"</span>
      <span class="pl-kos">}</span><span class="pl-kos">,</span>
      <span class="pl-kos">{</span>
        <span class="pl-s">"name"</span>: <span class="pl-s">"ntpd"</span><span class="pl-kos">,</span>
        <span class="pl-s">"description"</span>: <span class="pl-s">"NTP clock sync"</span>
      <span class="pl-kos">}</span><span class="pl-kos">,</span>
      ...<span class="pl-kos">.</span></pre></div>
<hr>
<h3>
<a id="user-content-gateway_status" class="anchor" href="#gateway_status" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>gateway_status</h3>
<ul>
<li>Returns gateway status data.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=gateway_status<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"598ecf3e7011e"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"gateway_status"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
  <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
    <span class="pl-s">"gateway_status"</span>: <span class="pl-kos">{</span>
      <span class="pl-s">"10.22.33.1"</span>: <span class="pl-kos">{</span>
        <span class="pl-s">"monitorip"</span>: <span class="pl-s">"8.8.8.8"</span><span class="pl-kos">,</span>
        <span class="pl-s">"srcip"</span>: <span class="pl-s">"10.22.33.100"</span><span class="pl-kos">,</span>
        <span class="pl-s">"name"</span>: <span class="pl-s">"GW_WAN"</span><span class="pl-kos">,</span>
        <span class="pl-s">"delay"</span>: <span class="pl-s">"4.415ms"</span><span class="pl-kos">,</span>
        <span class="pl-s">"stddev"</span>: <span class="pl-s">"3.239ms"</span><span class="pl-kos">,</span>
        <span class="pl-s">"loss"</span>: <span class="pl-s">"0.0%"</span><span class="pl-kos">,</span>
        <span class="pl-s">"status"</span>: <span class="pl-s">"none"</span>
      <span class="pl-kos">}</span>
    <span class="pl-kos">}</span>
  <span class="pl-kos">}</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-interface_stats" class="anchor" href="#interface_stats" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>interface_stats</h3>
<ul>
<li>Returns interface statistics data and information - the real interface name must be provided
not an alias of the interface such as "WAN" or "LAN"</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params:
<ul>
<li>
<strong>interface</strong> (required)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=interface_stats&amp;interface=em0<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"5b3a5bce65d01"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"interface_stats"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
  <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
    <span class="pl-s">"stats"</span>: <span class="pl-kos">{</span>
      <span class="pl-s">"inpkts"</span>: <span class="pl-c1">267017</span><span class="pl-kos">,</span>
      <span class="pl-s">"inbytes"</span>: <span class="pl-c1">21133408</span><span class="pl-kos">,</span>
      <span class="pl-s">"outpkts"</span>: <span class="pl-c1">205860</span><span class="pl-kos">,</span>
      <span class="pl-s">"outbytes"</span>: <span class="pl-c1">8923046</span><span class="pl-kos">,</span>
      <span class="pl-s">"inerrs"</span>: <span class="pl-c1">0</span><span class="pl-kos">,</span>
      <span class="pl-s">"outerrs"</span>: <span class="pl-c1">0</span><span class="pl-kos">,</span>
      <span class="pl-s">"collisions"</span>: <span class="pl-c1">0</span><span class="pl-kos">,</span>
      <span class="pl-s">"inmcasts"</span>: <span class="pl-c1">61618</span><span class="pl-kos">,</span>
      <span class="pl-s">"outmcasts"</span>: <span class="pl-c1">73</span><span class="pl-kos">,</span>
      <span class="pl-s">"unsuppproto"</span>: <span class="pl-c1">0</span><span class="pl-kos">,</span>
      <span class="pl-s">"mtu"</span>: <span class="pl-c1">1500</span>
    <span class="pl-kos">}</span>
  <span class="pl-kos">}</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-rule_get" class="anchor" href="#rule_get" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>rule_get</h3>
<ul>
<li>Returns the numbered list of loaded pf rules from a <code>pfctl -sr -vv</code> command
on the pfSense host.  An empty rule_number parameter causes all rules to be
returned.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params:
<ul>
<li>
<strong>rule_number</strong> (optional, default = null)</li>
</ul>
</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=rule_get&amp;rule_number=5<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"583c279b56958"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"rule_get"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
  <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
    <span class="pl-s">"rules"</span>: <span class="pl-kos">[</span>
      <span class="pl-kos">{</span>
        <span class="pl-s">"number"</span>: <span class="pl-c1">5</span><span class="pl-kos">,</span>
        <span class="pl-s">"rule"</span>: <span class="pl-s">"anchor \"openvpn/*\" all"</span><span class="pl-kos">,</span>
        <span class="pl-s">"evaluations"</span>: <span class="pl-s">"14134"</span><span class="pl-kos">,</span>
        <span class="pl-s">"packets"</span>: <span class="pl-s">"0"</span><span class="pl-kos">,</span>
        <span class="pl-s">"bytes"</span>: <span class="pl-s">"0"</span><span class="pl-kos">,</span>
        <span class="pl-s">"states"</span>: <span class="pl-s">"0"</span><span class="pl-kos">,</span>
        <span class="pl-s">"inserted"</span>: <span class="pl-s">"21188"</span><span class="pl-kos">,</span>
        <span class="pl-s">"statecreations"</span>: <span class="pl-s">"0"</span>
      <span class="pl-kos">}</span>
    <span class="pl-kos">]</span>
  <span class="pl-kos">}</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-send_event" class="anchor" href="#send_event" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>send_event</h3>
<ul>
<li>Performs a pfSense "send_event" command to cause various pfSense system
actions as is also available through the pfSense console interface.  The
following standard pfSense send_event combinations are permitted:-
<ul>
<li>filter: reload, sync</li>
<li>interface: all, newip, reconfigure</li>
<li>service: reload, restart, sync</li>
</ul>
</li>
<li>HTTP: <strong>POST</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X POST \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    --header <span class="pl-s"><span class="pl-pds">"</span>Content-Type: application/json<span class="pl-pds">"</span></span> \
    --data <span class="pl-s"><span class="pl-pds">"</span>[<span class="pl-cce">\"</span>interface reload all<span class="pl-cce">\"</span>]<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=send_event<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"58312bb3398bc"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"send_event"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-system_reboot" class="anchor" href="#system_reboot" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>system_reboot</h3>
<ul>
<li>Just as it says, reboots the system.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=system_reboot<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"58312bb3487ac"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"system_reboot"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-system_stats" class="anchor" href="#system_stats" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>system_stats</h3>
<ul>
<li>Returns various useful system stats.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=system_stats<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
  <span class="pl-s">"callid"</span>: <span class="pl-s">"5b3b511655589"</span><span class="pl-kos">,</span>
  <span class="pl-s">"action"</span>: <span class="pl-s">"system_stats"</span><span class="pl-kos">,</span>
  <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
  <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
    <span class="pl-s">"stats"</span>: <span class="pl-kos">{</span>
      <span class="pl-s">"cpu"</span>: <span class="pl-s">"20770421|20494981"</span><span class="pl-kos">,</span>
      <span class="pl-s">"mem"</span>: <span class="pl-s">"20"</span><span class="pl-kos">,</span>
      <span class="pl-s">"uptime"</span>: <span class="pl-s">"1 Day 21 Hours 25 Minutes 48 Seconds"</span><span class="pl-kos">,</span>
      <span class="pl-s">"pfstate"</span>: <span class="pl-s">"62/98000"</span><span class="pl-kos">,</span>
      <span class="pl-s">"pfstatepercent"</span>: <span class="pl-s">"0"</span><span class="pl-kos">,</span>
      <span class="pl-s">"temp"</span>: <span class="pl-s">""</span><span class="pl-kos">,</span>
      <span class="pl-s">"datetime"</span>: <span class="pl-s">"20180703Z103358"</span><span class="pl-kos">,</span>
      <span class="pl-s">"cpufreq"</span>: <span class="pl-s">""</span><span class="pl-kos">,</span>
      <span class="pl-s">"load_average"</span>: <span class="pl-kos">[</span>
        <span class="pl-s">"0.01"</span><span class="pl-kos">,</span>
        <span class="pl-s">"0.04"</span><span class="pl-kos">,</span>
        <span class="pl-s">"0.01"</span>
      <span class="pl-kos">]</span><span class="pl-kos">,</span>
      <span class="pl-s">"mbuf"</span>: <span class="pl-s">"1016/61600"</span><span class="pl-kos">,</span>
      <span class="pl-s">"mbufpercent"</span>: <span class="pl-s">"2"</span>
    <span class="pl-kos">}</span>
  <span class="pl-kos">}</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h3>
<a id="user-content-system_info" class="anchor" href="#system_info" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>system_info</h3>
<ul>
<li>Returns various useful system info.</li>
<li>HTTP: <strong>GET</strong>
</li>
<li>Params: none</li>
</ul>
<p><em>Example Request</em></p>
<div class="highlight highlight-source-shell"><pre>curl \
    -X GET \
    --silent \
    --insecure \
    --header <span class="pl-s"><span class="pl-pds">"</span>fauxapi-auth: &lt;auth-value&gt;<span class="pl-pds">"</span></span> \
    <span class="pl-s"><span class="pl-pds">"</span>https://&lt;host-address&gt;/fauxapi/v1/?action=system_info<span class="pl-pds">"</span></span></pre></div>
<p><em>Example Response</em></p>
<div class="highlight highlight-source-js"><pre><span class="pl-kos">{</span>
    <span class="pl-s">"callid"</span>: <span class="pl-s">"5e1d8ceb8ff47"</span><span class="pl-kos">,</span>
    <span class="pl-s">"action"</span>: <span class="pl-s">"system_info"</span><span class="pl-kos">,</span>
    <span class="pl-s">"message"</span>: <span class="pl-s">"ok"</span><span class="pl-kos">,</span>
    <span class="pl-s">"data"</span>: <span class="pl-kos">{</span>
        <span class="pl-s">"info"</span>: <span class="pl-kos">{</span>
            <span class="pl-s">"sys"</span>: <span class="pl-kos">{</span>
                <span class="pl-s">"platform"</span>: <span class="pl-kos">{</span>
                    <span class="pl-s">"name"</span>: <span class="pl-s">"VMware"</span><span class="pl-kos">,</span>
                    <span class="pl-s">"descr"</span>: <span class="pl-s">"VMware Virtual Machine"</span>
                <span class="pl-kos">}</span><span class="pl-kos">,</span>
                <span class="pl-s">"serial_no"</span>: <span class="pl-s">""</span><span class="pl-kos">,</span>
                <span class="pl-s">"device_id"</span>: <span class="pl-s">"719e8c91c2c43b820400"</span>
            <span class="pl-kos">}</span><span class="pl-kos">,</span>
            <span class="pl-s">"pfsense_version"</span>: <span class="pl-kos">{</span>
                <span class="pl-s">"product_version_string"</span>: <span class="pl-s">"2.4.5-DEVELOPMENT"</span><span class="pl-kos">,</span>
                <span class="pl-s">"product_version"</span>: <span class="pl-s">"2.4.5-DEVELOPMENT"</span><span class="pl-kos">,</span>
                <span class="pl-s">"product_version_patch"</span>: <span class="pl-s">"0"</span>
            <span class="pl-kos">}</span><span class="pl-kos">,</span>
            <span class="pl-s">"pfsense_remote_version"</span>: <span class="pl-kos">{</span>
                <span class="pl-s">"version"</span>: <span class="pl-s">"2.4.5.a.20200112.1821"</span><span class="pl-kos">,</span>
                <span class="pl-s">"installed_version"</span>: <span class="pl-s">"2.4.5.a.20191218.2354"</span><span class="pl-kos">,</span>
                <span class="pl-s">"pkg_version_compare"</span>: <span class="pl-s">"&lt;"</span>
            <span class="pl-kos">}</span><span class="pl-kos">,</span>
            <span class="pl-s">"os_verison"</span>: <span class="pl-s">"FreeBSD 11.3-STABLE"</span><span class="pl-kos">,</span>
            <span class="pl-s">"cpu_type"</span>: <span class="pl-kos">{</span>
                <span class="pl-s">"cpu_model"</span>: <span class="pl-s">"Intel(R) Core(TM) i7-7700 CPU @ 3.60GHz"</span><span class="pl-kos">,</span>
                <span class="pl-s">"cpu_count"</span>: <span class="pl-s">"4"</span><span class="pl-kos">,</span>
                <span class="pl-s">"logic_cpu_count"</span>: <span class="pl-s">"4 package(s)"</span><span class="pl-kos">,</span>
                <span class="pl-s">"cpu_freq"</span>: <span class="pl-s">""</span>
            <span class="pl-kos">}</span><span class="pl-kos">,</span>
            <span class="pl-s">"kernel_pti_status"</span>: <span class="pl-s">"enabled"</span><span class="pl-kos">,</span>
            <span class="pl-s">"mds_mitigation"</span>: <span class="pl-s">"inactive"</span><span class="pl-kos">,</span>
            <span class="pl-s">"bios"</span>: <span class="pl-kos">{</span>
                <span class="pl-s">"vendor"</span>: <span class="pl-s">"Phoenix Technologies LTD"</span><span class="pl-kos">,</span>
                <span class="pl-s">"version"</span>: <span class="pl-s">"6.00"</span><span class="pl-kos">,</span>
                <span class="pl-s">"date"</span>: <span class="pl-s">"07/29/2019"</span>
            <span class="pl-kos">}</span>
        <span class="pl-kos">}</span>
    <span class="pl-kos">}</span>
<span class="pl-kos">}</span></pre></div>
<hr>
<h2>
<a id="user-content-versions-and-testing" class="anchor" href="#versions-and-testing" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Versions and Testing</h2>
<p>The FauxAPI has been developed against the following pfSense versions</p>
<ul>
<li>
<strong>2.3.x</strong> - 2.3.2, 2.3.3, 2.3.4, 2.3.5</li>
<li>
<strong>2.4.x</strong> - 2.4.3, 2.4.4, 2.4.5</li>
<li>
<strong>2.5.x</strong> - 2.5.0-DEVELOPMENT-amd64-20200527-1410</li>
</ul>
<p>FauxAPI has not been tested against 2.3.0 or 2.3.1.  Additionally, it is apparent the pfSense
packaging technique changed significantly prior to 2.3.x so it is unlikely it will be backported
to anything prior to 2.3.0.</p>
<p>Testing is reasonable but does not achieve 100% code coverage within the FauxAPI
codebase.  Two client side test scripts (1x Bash, 1x Python) that both
demonstrate and test all possible server side actions are provided.  Under the
hood FauxAPI, performs real-time sanity checks and tests to make sure the user
supplied configurations will save, load and reload as expected.</p>
<p><strong>Shout Out:</strong> <em>Anyone that happens to know of <em>any</em> test harness or test code
for pfSense please get in touch - I'd very much prefer to integrate with existing
pfSense test infrastructure if it already exists.</em></p>
<h2>
<a id="user-content-releases" class="anchor" href="#releases" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>Releases</h2>
<h4>
<a id="user-content-v10---2016-11-20" class="anchor" href="#v10---2016-11-20" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>v1.0 - 2016-11-20</h4>
<ul>
<li>initial release</li>
</ul>
<h4>
<a id="user-content-v11---2017-08-12" class="anchor" href="#v11---2017-08-12" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>v1.1 - 2017-08-12</h4>
<ul>
<li>2x new API actions <strong>alias_update_urltables</strong> and <strong>gateway_status</strong>
</li>
<li>update documentation to address common points of confusion, especially the
requirement to provide the <em>full</em> config file not just the portion to be
updated.</li>
<li>testing against pfSense 2.3.2 and 2.3.3</li>
</ul>
<h4>
<a id="user-content-v12---2017-08-27" class="anchor" href="#v12---2017-08-27" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>v1.2 - 2017-08-27</h4>
<ul>
<li>new API action <strong>function_call</strong> allowing the user to reach deep into the inner
code infrastructure of pfSense, this feature is intended for people with a
solid understanding of PHP and pfSense.</li>
<li>the <code>credentials.ini</code> file now provides a way to control the permitted API
actions.</li>
<li>various update documentation updates.</li>
<li>testing against pfSense 2.3.4</li>
</ul>
<h4>
<a id="user-content-v13---2018-07-02" class="anchor" href="#v13---2018-07-02" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>v1.3 - 2018-07-02</h4>
<ul>
<li>add the <strong>config_patch</strong> function providing the ability to patch the system config,
thus allowing API users to make granular configuration changes.</li>
<li>added a "previous_config_file" response attribute to functions that cause write
operations to the running <code>config.xml</code>
</li>
<li>add the <strong>interface_stats</strong> function to help in determining the usage of an
interface to (partly) address <a href="https://github.com/ndejong/pfsense_fauxapi/issues/20">Issue #20</a>
</li>
<li>added a "number" attibute to the "rules" output making the actual rule number more
explict as described in <a href="https://github.com/ndejong/pfsense_fauxapi/issues/13">Issue #13</a>
</li>
<li>addressed a bug with the <strong>system_stats</strong> function that was preventing it from
returning, caused by an upstream change(s) in the pfSense code.</li>
<li>rename the confusing "owner" field in <code>credentials.ini</code> to "comment", legacy
configuration files using "owner" are still supported.</li>
<li>added a "source" attribute to the logs making it easier to grep fauxapi events,
for example <code>clog /var/log/system.log | grep fauxapi</code>
</li>
<li>plenty of documentation fixes and updates</li>
<li>added documentation highlighting features and capabilities that existed but were not
previously obvious</li>
<li>added the <a href="https://github.com/ndejong/pfsense_fauxapi/tree/master/extras"><code>extras</code></a> path
in the project repo as a better place to keep non-package files, <code>client-libs</code>, <code>examples</code>,
<code>build-tools</code> etc</li>
<li>testing against pfSense 2.3.5</li>
<li>testing against pfSense 2.4.3</li>
</ul>
<h4>
<a id="user-content-v14---2020-05-31" class="anchor" href="#v14---2020-05-31" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>v1.4 - 2020-05-31</h4>
<ul>
<li>Added <strong>system_info</strong> function to return various useful system information.</li>
<li>include include <code>phpsessionmanager.inc</code> since it is commonly required in other function calls</li>
<li>testing against pfSense 2.4.5</li>
<li>testing against pfSense 2.5.0 (pfSense-CE-2.5.0-DEVELOPMENT-amd64-20200527-1410.iso)</li>
</ul>
<h2>
<a id="user-content-fauxapi-license" class="anchor" href="#fauxapi-license" aria-hidden="true"><span aria-hidden="true" class="octicon octicon-link"></span></a>FauxAPI License</h2>
<pre><code>Copyright 2016-2020 Nicholas de Jong

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
</code></pre>
<!--READMEEND-->
</div>

<?php 
    include('foot.inc');
?>