## GitHub Copilot Chat

- Extension: 0.48.0 (prod)
- VS Code: 1.120.0 (0958016b2af9f09bb4257e0df4a95e2f90590f9f)
- OS: win32 10.0.26200 x64
- GitHub Account: bon7770

## Network

User Settings:

```json
  "http.systemCertificatesNode": true,
  "github.copilot.advanced.debug.useElectronFetcher": true,
  "github.copilot.advanced.debug.useNodeFetcher": false,
  "github.copilot.advanced.debug.useNodeFetchFetcher": true
```

Connecting to <https://api.github.com>:

- DNS ipv4 Lookup: Error (8578 ms): getaddrinfo ENOTFOUND api.github.com
- DNS ipv6 Lookup: timed out after 10 seconds
- Proxy URL: None (1 ms)
- Electron fetch (configured): Error (2217 ms): Error: net::ERR_NAME_NOT_RESOLVED
 at SimpleURLLoaderWrapper.<anonymous> (node:electron/js2c/utility_init:2:10684)
 at SimpleURLLoaderWrapper.emit (node:events:519:28)
  {"is_request_error":true,"network_process_crashed":false}
- Node.js https: Error (14 ms): Error: getaddrinfo ENOTFOUND api.github.com
 at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)
- Node.js fetch: Error (20 ms): TypeError: fetch failed
 at node:internal/deps/undici/undici:14902:13
 at process.processTicksAndRejections (node:internal/process/task_queues:103:5)
 at async n._fetch (c:\Users\ali\AppData\Local\Programs\Microsoft VS Code\0958016b2a\resources\app\extensions\copilot\dist\extension.js:5486:5229)
 at async n.fetch (c:\Users\ali\AppData\Local\Programs\Microsoft VS Code\0958016b2a\resources\app\extensions\copilot\dist\extension.js:5486:4541)
 at async u (c:\Users\ali\AppData\Local\Programs\Microsoft VS Code\0958016b2a\resources\app\extensions\copilot\dist\extension.js:5518:186)
 at async Ig._executeContributedCommand (file:///c:/Users/ali/AppData/Local/Programs/Microsoft%20VS%20Code/0958016b2a/resources/app/out/vs/workbench/api/node/extensionHostProcess.js:502:48675)
  Error: getaddrinfo ENOTFOUND api.github.com
   at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)

Connecting to <https://api.githubcopilot.com/_ping>:

- DNS ipv4 Lookup: Error (1 ms): getaddrinfo ENOTFOUND api.githubcopilot.com
- DNS ipv6 Lookup: timed out after 10 seconds
- Proxy URL: None (7 ms)
- Electron fetch (configured): timed out after 10 seconds
- Node.js https: timed out after 10 seconds
- Node.js fetch: timed out after 10 seconds

Connecting to <https://copilot-proxy.githubusercontent.com/_ping>:

- DNS ipv4 Lookup: timed out after 10 seconds
- DNS ipv6 Lookup: timed out after 10 seconds
- Proxy URL: None (3 ms)
- Electron fetch (configured): Error (4338 ms): Error: net::ERR_NETWORK_CHANGED
 at SimpleURLLoaderWrapper.<anonymous> (node:electron/js2c/utility_init:2:10684)
 at SimpleURLLoaderWrapper.emit (node:events:519:28)
  {"is_request_error":true,"network_process_crashed":false}
- Node.js https: Error (2538 ms): Error: getaddrinfo ENOTFOUND copilot-proxy.githubusercontent.com
 at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)
- Node.js fetch: Error (18 ms): TypeError: fetch failed
 at node:internal/deps/undici/undici:14902:13
 at process.processTicksAndRejections (node:internal/process/task_queues:103:5)
 at async n._fetch (c:\Users\ali\AppData\Local\Programs\Microsoft VS Code\0958016b2a\resources\app\extensions\copilot\dist\extension.js:5486:5229)
 at async n.fetch (c:\Users\ali\AppData\Local\Programs\Microsoft VS Code\0958016b2a\resources\app\extensions\copilot\dist\extension.js:5486:4541)
 at async u (c:\Users\ali\AppData\Local\Programs\Microsoft VS Code\0958016b2a\resources\app\extensions\copilot\dist\extension.js:5518:186)
 at async Ig._executeContributedCommand (file:///c:/Users/ali/AppData/Local/Programs/Microsoft%20VS%20Code/0958016b2a/resources/app/out/vs/workbench/api/node/extensionHostProcess.js:502:48675)
  Error: getaddrinfo ENOTFOUND copilot-proxy.githubusercontent.com
   at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)

Connecting to <https://mobile.events.data.microsoft.com>: Error (4 ms): Error: net::ERR_INTERNET_DISCONNECTED
 at SimpleURLLoaderWrapper.<anonymous> (node:electron/js2c/utility_init:2:10684)
 at SimpleURLLoaderWrapper.emit (node:events:519:28)
  {"is_request_error":true,"network_process_crashed":false}
Connecting to <https://dc.services.visualstudio.com>: Error (3 ms): Error: net::ERR_INTERNET_DISCONNECTED
 at SimpleURLLoaderWrapper.<anonymous> (node:electron/js2c/utility_init:2:10684)
 at SimpleURLLoaderWrapper.emit (node:events:519:28)
  {"is_request_error":true,"network_process_crashed":false}
Connecting to <https://copilot-telemetry.githubusercontent.com/_ping>: Error (21 ms): Error: getaddrinfo ENOTFOUND copilot-telemetry.githubusercontent.com
 at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)
Connecting to <https://copilot-telemetry.githubusercontent.com/_ping>: Error (15 ms): Error: getaddrinfo ENOTFOUND copilot-telemetry.githubusercontent.com
 at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)
Connecting to <https://default.exp-tas.com>: Error (25 ms): Error: getaddrinfo ENOTFOUND default.exp-tas.com
 at GetAddrInfoReqWrap.onlookupall [as oncomplete] (node:dns:122:26)

Number of system certificates: 131

## Documentation

In corporate networks: [Troubleshooting firewall settings for GitHub Copilot](https://docs.github.com/en/copilot/troubleshooting-github-copilot/troubleshooting-firewall-settings-for-github-copilot).
