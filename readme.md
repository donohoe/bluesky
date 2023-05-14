# Bluesky PHP Client #

This is a very basic php client that allows you to post a combination of text (with link support), embedded links, or an image.

That is it.

## Setup ##

The main setup required for this to work is updating the account information for your Bluesky account.

A few words of warning:

1. Move the JSON file into a non-public part of your server. Seriously. JSON files are readable.
2. Never use your actual account password. Bluesky allows you to generate an app password. Use that. Seriously.
3. FOR REAL - move the JSON file somewhere where it cannot be viewed publicly.

## Usage ##

The examples should tell you all you need to know. 

In the case of uploading images, know that Blue sky currently has a file size limit of 976.56KB.

## Support ##

PRs are welcome but keep in mind that (a) I have a full-time job, and (b) this is meant to remain simple.

Here are a few changes that I'd welcome, and eventually plan to make:

* Move from JSON to PHP file for storing credentials so not easily readable publicly by mistake
* Support for OpenGraph scraping of link URLs to pre-populate the title, description, and image fields
* Support for multiple images
* Fix typos. I'm so sorry. You'd think after all these years I'd be better...

Suggestions are welcome too.

You can find me on Bluesky at [@donohoe.dev](https://staging.bsky.app/profile/donohoe.dev).
