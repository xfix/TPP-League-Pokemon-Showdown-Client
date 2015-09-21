#!/usr/bin/env python3
# I gave up trying to do this in asynchronous JavaScript.

from urllib.request import urlopen, urlretrieve
from json import loads
from os import makedirs
from os.path import isfile
from re import escape

global_emotes_link = 'http://twitchemotes.com/api_cache/v2/global.json'
subscriber_emotes_link = 'http://twitchemotes.com/api_cache/v2/subscriber.json'

subscribe_to = ['turbo', 'twitchplayspokemon', 'puncayshun', 'srkevo1', 'lffn']

emote_path = '../fx/emotes/'

regex_path = '../data/emoteregex.js'

downloaded_emotes = []

def slurp_binary(handle):
    return handle.readall().decode()

def read_api(url):
    return loads(slurp_binary(urlopen(url)))

def parse_global():
    api_result = read_api(global_emotes_link)
    template = api_result['template']['small']
    emotes = api_result['emotes']
    for name, details in emotes.items():
        download_emote(name, template, details['image_id'])

def parse_subscribers():
    api_result = read_api(subscriber_emotes_link)
    template = api_result['template']['small']
    channels = api_result['channels']
    for subscriber in subscribe_to:
        emotes = channels[subscriber]['emotes']
        for emote in emotes:
            download_emote(emote['code'], template, emote['image_id'])

def download_emote(name, template, image_id):
    downloaded_emotes.append(name)

    output_file = emote_path + name + '.png'
    if isfile(output_file):
        return

    print(name)
    link = 'http:' + template.format(image_id=image_id)
    urlretrieve(link, output_file)

def finish():
    downloaded_emotes.sort()
    escaped_emotes = map(escape, downloaded_emotes)
    with open(regex_path, 'w') as output:
        output.write("var emoteRegExp = /\\b(?:{})\\b/g;\n".format('|'.join(escaped_emotes)))

def main():
    makedirs(emote_path, exist_ok=True)
    parse_global()
    parse_subscribers()
    finish()

if __name__ == '__main__':
    main()
