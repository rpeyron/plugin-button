# plugin-button
Dokuwiki Plugin

Button Plugin for dokuwiki 
https://www.dokuwiki.org/plugin:button
http://www.lprp.fr/wiki/doku.php/dokuwikibutton


@license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
@author     Rémi Peyronnet  <remi+button@via.ecp.fr>

## Tests & devcontainer

A devcontainer definition is defined in .devcontainer/devcontainer.json.

It will load a default dokuwiki installation to dev and test:
- [dokuwiki](https://hub.docker.com/r/linuxserver/dokuwiki) docker container provided by linuxserver, built with latest dokuwiki version, and base image seems to be frequently updated with PHP
- configuration copied from tests/conf (initialized with admin test:test ; not updated)
- data/pages and data/media mounted from test/data

In vscode click on the blue button "Open remote" on the bottom left corner and select "Reopen in container" (requires docker installation). Open http://localhost/