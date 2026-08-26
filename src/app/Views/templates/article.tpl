{extends file="layout.tpl"}

{block name="title"}{if $article}{$article->title}{else}Article not found{/if}{/block}

{block name="content"}
    <p><a href="/">&larr; Back to home</a></p>

    {if !$article}
        <h1>Article not found</h1>
    {else}
        <h1>{$article->title}</h1>

        {if $article->image}
            <p><img src="{$article->image}" alt="{$article->title}" style="max-width:100%;height:auto"></p>
        {/if}

        <p>
            <small>
                Published: {$article->publishedAt|truncate:10:''}
                &middot; views: {$article->views}
                {if $article->categories}
                    &middot; categories:
                    {foreach $article->categories as $category}
                        <a href="/category?id={$category->id}">{$category->name}</a>{if !$category@last}, {/if}
                    {/foreach}
                {/if}
            </small>
        </p>

        <p><em>{$article->description}</em></p>

        <div>{$article->text|escape|nl2br nofilter}</div>

        {if $similar}
            <h2>Similar articles</h2>
            <ul>
                {foreach $similar as $item}
                    <li><a href="/article?id={$item->id}">{$item->title}</a></li>
                {/foreach}
            </ul>
        {/if}
    {/if}
{/block}
