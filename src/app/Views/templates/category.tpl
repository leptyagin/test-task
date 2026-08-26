{extends file="layout.tpl"}

{block name="title"}{if $category}{$category->name}{else}Category not found{/if}{/block}

{block name="content"}
    <p><a href="/">&larr; Back to home</a></p>

    {if !$category}
        <h1>Category not found</h1>
    {else}
        <h1>{$category->name}</h1>
        {if $category->description}
            <p>{$category->description}</p>
        {/if}

        <form method="get" action="/category">
            <input type="hidden" name="id" value="{$category->id}">
            <label>
                Sort by:
                <select name="sort" onchange="this.form.submit()">
                    <option value="date" {if $sort == 'date'}selected{/if}>publication date</option>
                    <option value="views" {if $sort == 'views'}selected{/if}>views</option>
                </select>
            </label>
            <label>
                <select name="dir" onchange="this.form.submit()">
                    <option value="desc" {if $dir == 'desc'}selected{/if}>descending</option>
                    <option value="asc" {if $dir == 'asc'}selected{/if}>ascending</option>
                </select>
            </label>
            <noscript><button type="submit">Apply</button></noscript>
        </form>

        {if !$page->items}
            <p>There are no articles in this category yet.</p>
        {else}
            <ol start="{$page->offset() + 1}">
                {foreach $page->items as $article}
                    <li>
                        <a href="/article?id={$article->id}">{$article->title}</a>
                        <small>&mdash; views: {$article->views}, {$article->publishedAt|truncate:10:''}</small>
                    </li>
                {/foreach}
            </ol>

            <nav>
                {if $page->hasPrev()}
                    <a href="/category?id={$category->id}&amp;sort={$sort}&amp;dir={$dir}&amp;page={$page->page - 1}">&larr; Previous</a>
                {/if}
                <span>page {$page->page} of {$page->pages()}</span>
                {if $page->hasNext()}
                    <a href="/category?id={$category->id}&amp;sort={$sort}&amp;dir={$dir}&amp;page={$page->page + 1}">Next &rarr;</a>
                {/if}
            </nav>
        {/if}
    {/if}
{/block}
