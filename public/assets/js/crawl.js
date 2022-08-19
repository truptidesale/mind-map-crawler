$(document).ready(function(){
  $.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  $("#crawler_form").on('submit', function(e){
    e.preventDefault();
    $('#post_crawl').hide();
    $('#pre_crawl').show();
    $('#spinner').show();
    $('#crawled_data_wrapper').hide();
    $('#error_alert').hide();

    $.ajax({
        url: '/crawler',
        type: "POST",
        data: {
            url: $('#website_url').val(),
            count: $('#page_count').val()
        },
        dataType : 'json',
        success: function(response) {
          $('#average_load_time').html(response.average_load_time);
          $('#average_word_count').html(response.average_word_count);
          $('#average_page_title_length').html(response.average_page_title_length);
          $('#post_crawl').show();
          $('#result_page_count').html(response.table_data.length);

          $('#crawled_data').DataTable( {
            destroy: true,
            data: response.table_data,
            columns: [
              { title: "HTTP status" },
              { title: "Page" },
              { title: "Unique images" },
              { title: "Unique internal links" },
              { title: "Unique external links" },
              { title: "Page load time" },
              { title: "Page word count" },
              { title: "Page title length" },
            ]
          });
        },
        error: function(data, textStatus, jqXHR) {
          console.log(data.error);
          //show error div
          $('#error_alert').show();
          $('#pre_crawl').hide();
          $('#spinner').hide();
        },
        complete: function() {
          $('#pre_crawl').hide();
          $('#spinner').hide();
          $('#crawled_data_wrapper').show();
        }
    });
  });
});
