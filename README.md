newsbias
========

A news aggregator based on bias

( When I get the chance I will clean up this code, but for now feel free to poke around )

Brief

State controlled media versus free media are becoming ever more disparate. The idea behind The News By Us came from a simple desire to see the differences in opinion about the same topic from different sources. In this way, one would easily be able to see just how different the "facts" were being portrayed, and find the "truth" somewhere in the middle.


Methodology (short explanation)

TNBU works from RSS feeds. RSS feeds are pulled in and the information disseminated. The title and content of each item is then stored in a MySQL database. The next task is to find common topics. The best way of finding topics is by finding the nouns, and their frequency of occurence, per article. The title and content fields are now compared, word for word, against the Princeton Wordnet database. This command line db allows us to see if a word is an adjective, adverb, or verb. Using a process of elimination, one can deduce whether a word is a noun or not. Once all words are processed, the nouns are put back into a string with the most frequently occuring words first.

Next, these nouns are compared to the topics table. If the nouns match a topic 30% or greater, they are added to that topic. If they match no topics, and their length is greater than 5 words, a new topic is created using these words. (This is to stop two or three word topics from being created, resulting in a 50% or 100% match if only two words are matched up). If they do not create a new topic, they are given a topic ID of 0. In this way, all articles are now grouped into topics. The next step is to shelve them left or right based on their "trust" rating. The trust rating is determined from the source. A source, such as news24, is given an initial trust rating. This trust rating is then carried through to every article from that source, giving them an intitial trust rating. Now, each article can be voted on. When an article is voted on, that vote is saved in the Vote table, and the article is given the new, averaged vote. At the end of every day, two cron jobs get run. One: update the 0 topics, and two: update the base trust rating for each topic. All 0 topics are re-compared against all existing topics, and moved into matching topics. Those who do not match, are kept 0 topics and will get re-evaluated at the end of the next day. The sources are then updated with new trust ratings. An average of all articles trust rating is made from the Vote table. This new, averaged rating is then made the base rating for that source. Thus, all new articles have the new rating of trust, dynamically created by users.

Conclusion

This is a simplified explanation of the process, but explains each step clearly. The end result is a dynamic news agreggator showing bias in news articles (The News By Us / The News Bias).
