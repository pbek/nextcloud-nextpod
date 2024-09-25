# Changelog

## 0.7.6
- Updated and tested app for Nextcloud 30 (for [#11](https://github.com/pbek/nextcloud-nextpod/issues/11))
- Updated dependencies

## 0.7.5
- Updated and tested app for Nextcloud 29 (for [#9](https://github.com/pbek/nextcloud-nextpod/issues/9))
- Updated dependencies

## 0.7.4
- Updated and tested app for Nextcloud 28
- Updated dependencies

## 0.7.3
- The episode and podcast names are now shown in the episode description modal dialog
  and the cursor was changed to `text` to indicate that the text is selectable
- Dependencies were updated

## 0.7.2
- You can now select text in the episode description modal dialog

## 0.7.1
- Updated and tested app for Nextcloud 27

## 0.7.0
- The styling of the episode description was improved
  - Images will now be scaled down to fit the screen width
  - The links in the description of the episode are now better visible
- The parsing of the extra data of actions (like name and description)
  is now more fault-tolerant to be able to show the data even if the
  podcast changes its item URLs all the time (for [#7](https://github.com/pbek/nextcloud-nextpod/issues/7))

## 0.6.2
- The links in the description of the episode actions are now opened
  in a new browser window/tab when clicked

## 0.6.1
- The styling of the episode description was improved

## 0.6.0
- The play status is now optionally stored while playing an episode
  (for [#4](https://github.com/pbek/nextcloud-nextpod/issues/4))
- The action menu order of episode items was changed to make more sense

## 0.5.1
- The episode extra data fetching for podcasts added in AntennaPod is now fixed
  (for [#4](https://github.com/pbek/nextcloud-nextpod/issues/4))

## 0.5.0
- The episode player now automatically starts playing at the synced position
  (for [#3](https://github.com/pbek/nextcloud-nextpod/issues/3))

## 0.4.1
- Updated and tested app for Nextcloud 26

## 0.4.0
- Episode and podcast lists are now reloaded automatically every 10 minutes

## 0.3.1
- An issue with the note headline of created notes of an episode in
  [Nextcloud Notes](https://apps.nextcloud.com/apps/notes) was fixed
  (for [#1](https://github.com/pbek/nextcloud-nextpod/issues/1)) 

## 0.3.0
- You now can create a note of an episode in [Nextcloud Notes](https://apps.nextcloud.com/apps/notes)
  (for [#1](https://github.com/pbek/nextcloud-nextpod/issues/1)) 

## 0.2.0
- You can now click an episode to show the description of the episode
- The episode image detection was now made more fault-tolerant to always be able to show an episode image

## 0.1.1
- Extra data of actions and podcast data of subscriptions are now side-loaded correctly
  when clicking the navigation headlines

## 0.1.0
- Initial release with sync server and UI for episodes and podcasts
