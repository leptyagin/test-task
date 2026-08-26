{extends file="layout.tpl"}

{block name="title"}Articles{/block}

{block name="content"}
    <h1>Articles</h1>
    <p><a href="/categories">Categories</a></p>

    {if !$articles}
        <p>No articles yet. Load demo data: <code>php bin/console db:seed</code>.</p>
    {else}
        {foreach $articles as $article}
            <article>
                <h2><a href="/article?id={$article->id}">{$article->title}</a></h2>
                <p>{$article->description}</p>
                <p>
                    Views: {$article->views}
                    {if $article->categories}
                        &middot; categories:
                        {foreach $article->categories as $category}{$category->name}{if !$category@last}, {/if}{/foreach}
                    {/if}
                </p>
            </article>
        {/foreach}
    {/if}
{/block}
