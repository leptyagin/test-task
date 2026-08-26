{extends file="layout.tpl"}

{block name="title"}Blog{/block}

{block name="content"}
    <h1>Blog</h1>

    {if !$blocks}
        <p>No categories with articles found. Load demo data: <code>php bin/console db:seed</code>.</p>
    {else}
        {foreach $blocks as $block}
            <section>
                <h2>{$block.category->name}</h2>
                {if $block.category->description}
                    <p>{$block.category->description}</p>
                {/if}

                <ul>
                    {foreach $block.articles as $article}
                        <li>
                            <a href="/article?id={$article->id}">{$article->title}</a>
                            <small>&mdash; {$article->publishedAt|truncate:10:''}</small>
                        </li>
                    {/foreach}
                </ul>

                <p><a href="/category?id={$block.category->id}"><strong>All articles &rarr;</strong></a></p>
            </section>
        {/foreach}
    {/if}
{/block}
