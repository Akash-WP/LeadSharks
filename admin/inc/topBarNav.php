<style>
  .user-img{
        position: absolute;
        height: 27px;
        width: 27px;
        object-fit: cover;
        left: -7%;
        top: -12%;
  }
  .btn-rounded{
        border-radius: 50px;
  }
</style>
<!-- Navbar -->
      <nav class="main-header navbar navbar-expand navbar-light border-top-0  border-left-0 border-right-0 text-sm shadow-sm bg-gradient-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
          <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
          </li>
          <li class="nav-item d-none d-sm-inline-block">
            <a href="<?php echo base_url ?>" class="nav-link"><b><?php echo (!isMobileDevice()) ? $_settings->info('name'):$_settings->info('short_name'); ?></b></a>
          </li>
        </ul>
        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
          <!-- Navbar Search -->
          <!-- <li class="nav-item">
            <a class="nav-link" data-widget="navbar-search" href="#" role="button">
            <i class="fas fa-search"></i>
            </a>
            <div class="navbar-search-block">
              <form class="form-inline">
                <div class="input-group input-group-sm">
                  <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                  <div class="input-group-append">
                    <button class="btn btn-navbar" type="submit">
                    <i class="fas fa-search"></i>
                    </button>
                    <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                    <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </li> -->
<li class="nav-item position-relative" style="padding: 0 10px;">
  <a class="nav-link position-relative d-flex align-items-center justify-content-center" href="javascript:void(0);" id="openMessageSidebar" title="Send Message" style="font-size: 18px; padding: 6px 10px; border-radius: 6px;">
    <i class="fa fa-envelope text-primary" style="font-size: 20px;"></i>
    <span id="unreadCountBadge"
          class="badge badge-danger badge-pill position-absolute"
          style="top: 2px; right: 2px; font-size: 10px; min-width: 16px; height: 16px; padding: 2px 4px; line-height: 12px; display: none;">
      0
    </span>
  </a>
</li>



<!-- Message Sidebar -->

<div id="messageSidebar" class="message-sidebar shadow">
  <div class="message-header d-flex justify-content-between align-items-center p-3 border-bottom bg-gradient-primary text-white">
  <div class="d-flex align-items-center">
    <button class="btn btn-sm btn-light rounded-circle mr-2" id="backToUserList" title="Back to Users">
      <i class="fas fa-arrow-left text-primary"></i>
    </button>
    <strong><i class="fas fa-comments mr-2"></i>Messages</strong>
  </div>
  <button class="btn btn-sm btn-light rounded-circle" id="closeMessageSidebar">&times;</button>
</div>


<!-- Replace the select dropdown -->
<div class="user-list p-2 border-bottom bg-white" id="userList">
  <?php
  $uid = $_settings->userdata('id');
  $user_qry = $conn->query("SELECT u.id, CONCAT(u.firstname, ' ', u.lastname) AS name, 
                                  (SELECT COUNT(*) FROM messages 
                                   WHERE sender_id = u.id AND recipient_id = $uid AND is_read = 0) AS unread_count 
                            FROM users u 
                            WHERE u.id != $uid");
  while ($u = $user_qry->fetch_assoc()):
  ?>
    <div class="user-item d-flex justify-content-between align-items-center p-2 border rounded mb-1 user-selectable" data-user-id="<?= $u['id'] ?>">
      <span><?= ucwords($u['name']) ?></span>
      <?php if ($u['unread_count'] > 0): ?>
        <span class="badge badge-danger badge-pill unread-badge"><?= $u['unread_count'] ?></span>
      <?php endif; ?>
    </div>
  <?php endwhile; ?>
</div>


  <div class="message-body" id="messageThread">
    <!-- Messages will be dynamically appended here -->
    <div class="empty-state text-center py-5">
      <i class="fas fa-comment-dots fa-3x text-muted mb-3"></i>
      <p class="text-muted">Select a user to start chatting</p>
    </div>
  </div>

  <div class="message-input border-top p-3 bg-light">
    <form id="sidebarMessageForm">
      <div class="form-group mb-2">
        <div class="input-group">
          <textarea id="sidebarMessageText" name="message" class="form-control" rows="2" maxlength="300" placeholder="Type your message..." required></textarea>
          <div class="input-group-append">
            <button type="submit" class="btn btn-primary" title="Send" id="sendMessageBtn">
              <i class="fas fa-paper-plane"></i>
            </button>
          </div>
        </div>
        <small class="form-text text-muted text-right"><span id="charCount">0</span>/300</small>
      </div>
    </form>
  </div>
</div>


          <!-- Messages Dropdown Menu -->
          <li class="nav-item">
            <div class="btn-group nav-link">
                  <button type="button" class="btn btn-rounded badge badge-light dropdown-toggle dropdown-icon" data-toggle="dropdown">
                    <span><img src="<?php echo validate_image($_settings->userdata('avatar')) ?>" class="img-circle elevation-2 user-img" alt="User Image"></span>
                    <span class="ml-3"><?php echo ucwords($_settings->userdata('firstname').' '.$_settings->userdata('lastname')) ?></span>
                    <span class="sr-only">Toggle Dropdown</span>
                  </button>
                  <div class="dropdown-menu" role="menu">
                    <a class="dropdown-item" href="<?php echo base_url.'admin/?page=user' ?>"><span class="fa fa-user"></span> My Account</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="<?php echo base_url.'/classes/Login.php?f=logout' ?>"><span class="fas fa-sign-out-alt"></span> Logout</a>
                  </div>
              </div>
          </li>
          <li class="nav-item">
            
          </li>
         <!--  <li class="nav-item">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
            <i class="fas fa-th-large"></i>
            </a>
          </li> -->
        </ul>
      </nav>

<style>
.message-sidebar {
  position: fixed;
  top: 0;
  right: -400px;
  width: 350px;
  height: 100vh;
  background: #fff;
  z-index: 1050;
  transition: right 0.3s ease-in-out;
  box-shadow: -2px 0 15px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  border-left: 1px solid rgba(0, 0, 0, 0.1);
}

.message-sidebar.open {
  right: 0;
}

.message-header {
  border-bottom: 1px solid rgba(255, 255, 255, 0.2) !important;
}

.message-body {
  flex: 1;
  overflow-y: auto;
  padding: 15px;
  background: #f8f9fa;
  background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  background-size: 150px;
  background-repeat: repeat;
  background-position: center;
}

.message {
  margin-bottom: 15px;
  max-width: 80%;
  clear: both;
  transition: all 0.3s ease;
}

.message:hover {
  transform: translateY(-2px);
}

.message.left {
  text-align: left;
  margin-right: auto;
}

.message.right {
  text-align: right;
  margin-left: auto;
}

.message .bubble {
  display: inline-block;
  padding: 10px 15px;
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  position: relative;
  word-break: break-word;
}

.message.right .bubble {
  background: #007bff;
  color: white;
  border-bottom-right-radius: 4px;
}

.message.left .bubble {
  border-bottom-left-radius: 4px;
  background: #fff;
}

.message .meta {
  font-size: 11px;
  margin-top: 3px;
  color: #6c757d;
  padding: 0 5px;
}

.message.right .meta {
  text-align: right;
}

.message .user {
  font-weight: 600;
  font-size: 12px;
  margin-bottom: 3px;
  color: #495057;
}

.message.right .user {
  color: rgba(255, 255, 255, 0.8);
}

.empty-state {
  opacity: 0.6;
}

.message-input {
  box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.03);
}

/* Scrollbar styling */
.message-body::-webkit-scrollbar {
  width: 6px;
}

.message-body::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.05);
}

.message-body::-webkit-scrollbar-thumb {
  background: rgba(0, 123, 255, 0.3);
  border-radius: 3px;
}

.message-body::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 123, 255, 0.5);
}

/* Typing indicator */
.typing-indicator {
  display: inline-block;
  padding: 10px 15px;
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.typing-indicator span {
  height: 8px;
  width: 8px;
  background: #6c757d;
  border-radius: 50%;
  display: inline-block;
  margin: 0 2px;
  animation: bounce 1.5s infinite ease-in-out;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes bounce {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-5px); }
}
</style>


      <!-- /.navbar -->

      <script>
let selectedUserId = null;

$(document).ready(function() {
  // Open sidebar
  $('#openMessageSidebar').on('click', function() {
    $('#messageSidebar').addClass('open');
    $('#unreadCountBadge').hide();
  });

  // Close sidebar
  $('#closeMessageSidebar, #cancelMessage').on('click', function() {
    $('#messageSidebar').removeClass('open');
    $('#sidebarMessageForm')[0].reset();
    $('#messageThread').html(`<div class="empty-state text-center py-5">
      <i class="fas fa-comment-dots fa-3x text-muted mb-3"></i>
      <p class="text-muted">Select a user to start chatting</p>
    </div>`);
    selectedUserId = null;
  });

  // On user click
  $(document).on('click', '.user-item', function() {
    $('.user-item').removeClass('bg-primary text-white');
    $(this).addClass('bg-primary text-white');
    selectedUserId = $(this).data('user-id');

    // Hide unread badge
    $(this).find('.unread-badge').remove();
    $('#userList').hide(); // Hide the list of users


    loadMessages(selectedUserId);
  });

  // Submit message
  $('#sidebarMessageForm').on('submit', function(e) {
    e.preventDefault();
    const message = $('#sidebarMessageText').val().trim();
    if (!selectedUserId || !message) {
      alert("Please select a user and type a message.");
      return;
    }

    $.ajax({
      url: '../classes/Messaging.php?f=send',
      method: 'POST',
      data: { recipient: selectedUserId, message },
      dataType: 'json',
      success: function(resp) {
        if (resp.status === 'success') {
          $('#messageThread').append(`
            <div class="message right">
              <div class="user">You</div>
              <div class="bubble">${message}</div>
            </div>
          `);
          $('#sidebarMessageText').val('');
          $('#charCount').text('0');
          $('#messageThread').scrollTop($('#messageThread')[0].scrollHeight);
        } else {
          alert('Failed to send message.');
        }
      }
    });
  });

  // Character count
  $('#sidebarMessageText').on('input', function() {
    $('#charCount').text($(this).val().length);
  });
});

// Load messages
function loadMessages(withUserId) {
  $.ajax({
    url: '../classes/get_messages.php?with=' + withUserId,
    method: 'GET',
    dataType: 'json',
    success: function(data) {
      $('#messageThread').empty();
      const currentUserId = <?= $_settings->userdata('id') ?>;
      data.forEach(msg => {
        const alignClass = msg.sender_id == currentUserId ? 'right' : 'left';
        const userLabel = msg.sender_id == currentUserId ? 'You' : msg.sender_name;
        $('#messageThread').append(`
          <div class="message ${alignClass}">
            <div class="user">${userLabel}</div>
            <div class="bubble">${msg.message}</div>
          </div>
        `);
      });

      // scroll to bottom
      $('#messageThread').scrollTop($('#messageThread')[0].scrollHeight);

      // mark as read
      $.post('../classes/Messaging.php?f=mark_read', { from_user: withUserId });
    },
    error: function() {
      alert("Failed to load messages.");
    }
  });
}

$('#backToUserList').on('click', function () {
  $('#userList').show(); // Show user list again
  $('#messageThread').html(`<div class="empty-state text-center py-5">
    <i class="fas fa-comment-dots fa-3x text-muted mb-3"></i>
    <p class="text-muted">Select a user to start chatting</p>
  </div>`);
  selectedUserId = null;

  // Un-highlight any selected user
  $('.user-item').removeClass('bg-primary text-white');
});

$(document).on('click', '.user-item', function () {
  $('.user-item').removeClass('bg-primary text-white');
  $(this).addClass('bg-primary text-white');
  selectedUserId = $(this).data('user-id');

  $(this).find('.unread-badge').remove();

  $('#userList').hide(); // Hide list when chat starts
  loadMessages(selectedUserId);
});

function updateUnreadBadge() {
  $.ajax({
    url: '../classes/Messaging.php?f=unread_count',
    method: 'GET',
    dataType: 'json',
    success: function (resp) {
      if (resp.count > 0) {
        $('#unreadCountBadge').text(resp.count).show();
      } else {
        $('#unreadCountBadge').hide();
      }
    },
    error: function () {
      console.warn("Failed to fetch unread message count.");
    }
  });
}

$(document).ready(function () {
  updateUnreadBadge();

  // optionally refresh every 30 seconds
  setInterval(updateUnreadBadge, 30000);
});

</script>
